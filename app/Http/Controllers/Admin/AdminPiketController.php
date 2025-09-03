<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PiketRoster;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class AdminPiketController extends Controller
{
    /** Helper: nama kolom tanggal di tabel piket_rosters */
    protected function rosterDateCol(): string
    {
        // Prioritaskan 'date'; jika tidak ada, pakai 'tanggal'
        if (Schema::hasColumn('piket_rosters', 'date'))     return 'date';
        if (Schema::hasColumn('piket_rosters', 'tanggal'))  return 'tanggal';
        // Fallback aman (biar error-nya jelas jika dua-duanya tak ada)
        return 'date';
    }

    /** Helper: map catatan -> note agar view tetap konsisten */
    protected function attachAliases($rowOrCollection): void
    {
        if (!$rowOrCollection) return;

        $apply = function ($row) {
            // alias 'note' dari 'catatan' kalau belum ada accessor
            if (!isset($row->note) && isset($row->catatan)) {
                $row->note = $row->catatan;
            }
            // alias 'name' (jika belum ada kolom name—biarkan null agar view aman)
            if (!isset($row->name)) {
                $row->name = null;
            }
        };

        if ($rowOrCollection instanceof \Illuminate\Support\Collection) {
            $rowOrCollection->each($apply);
        } else {
            $apply($rowOrCollection);
        }
    }

    public function index(Request $r)
    {
        $tz    = config('app.timezone','Asia/Jakarta');
        $now   = Carbon::now($tz)->startOfDay();
        $today = $now->toDateString();
        $col   = $this->rosterDateCol();

        // ====== Filter akun piket ======
        $q      = trim((string)$r->get('q',''));
        $active = $r->filled('active') ? $r->get('active') : null; // "1" / "0" / null

        $users = User::query()
            ->where('role','piket')
            ->when($q, fn($x)=> $x->where(function($s) use($q){
                $s->where('name','like',"%{$q}%")
                  ->orWhere('email','like',"%{$q}%");
            }))
            ->when($active !== null, fn($x)=> $x->where('is_active',(int)$active))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total'    => User::where('role','piket')->count(),
            'aktif'    => User::where('role','piket')->where('is_active',1)->count(),
            'nonaktif' => User::where('role','piket')->where('is_active',0)->count(),
        ];

        // ====== Roster hari ini & 7 hari ke depan ======
        $rosterToday = PiketRoster::with('user:id,name')
            ->where($col, $today)     // gunakan nama kolom dinamis
            ->first();

        $rosterNext  = PiketRoster::with('user:id,name')
            ->where($col, '>=', $today)
            ->orderBy($col)
            ->limit(8)->get();

        // alias agar view bisa akses $row->note / $row->name
        $this->attachAliases($rosterToday);
        $this->attachAliases($rosterNext);

        // Pilihan pegawai (untuk input roster)
        $pegawai = User::whereIn('role',['guru','tu','kepsek'])
            ->where('is_active',1)
            ->orderBy('name')
            ->get(['id','name']);

        return view('admin.piket.index', [
            'users'       => $users,
            'summary'     => $summary,
            'q'           => $q,
            'active'      => $active,
            'today'       => $now,          // Carbon instance
            'rosterToday' => $rosterToday,  // atau null
            'rosterNext'  => $rosterNext,   // koleksi
            'pegawai'     => $pegawai,
        ]);
    }

    // ===== CRUD Akun Piket =====

    public function create()
    {
        return view('admin.piket.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'      => ['required','string','max:100'],
            'email'     => ['required','email','max:150','unique:users,email'],
            'password'  => ['required','string','min:6'],
            'is_active' => ['nullable','boolean'],
        ]);

        User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'role'      => 'piket',
            'password'  => Hash::make($data['password']),
            'is_active' => (int)($data['is_active'] ?? 1),
        ]);

        return redirect()->route('admin.piket.index')->with('success','Akun piket dibuat.');
    }

    public function edit(User $user)
    {
        abort_unless($user->role === 'piket', 404);
        return view('admin.piket.edit', compact('user'));
    }

    public function update(Request $r, User $user)
    {
        abort_unless($user->role === 'piket', 404);

        $data = $r->validate([
            'name'      => ['required','string','max:100'],
            'email'     => ['required','email','max:150', Rule::unique('users','email')->ignore($user->id)],
            'password'  => ['nullable','string','min:6'],
            'is_active' => ['nullable','boolean'],
        ]);

        $user->name      = $data['name'];
        $user->email     = $data['email'];
        $user->is_active = (int)($data['is_active'] ?? 1);
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return redirect()->route('admin.piket.index')->with('success','Akun piket diperbarui.');
    }

    public function reset(User $user)
    {
        abort_unless($user->role === 'piket', 404);
        $user->password = Hash::make('piket123');
        $user->save();

        return back()->with('success','Password direset ke "piket123".');
    }

    public function destroy(User $user)
    {
        abort_unless($user->role === 'piket', 404);
        $user->delete();
        return back()->with('success','Akun piket dihapus.');
    }

    // ===== Roster =====

    public function rosterStore(Request $r)
    {
        $data = $r->validate([
            'date'    => ['required','date'],
            'user_id' => ['nullable','exists:users,id'],
            'name'    => ['nullable','string','max:100'], // akan disimpan hanya jika kolom 'name' ada
            'note'    => ['nullable','string','max:200'], // dipetakan ke 'catatan'
            'shift'   => ['nullable','string','max:20'],
        ]);

        if (empty($data['user_id']) && empty($data['name'])) {
            return back()->withErrors(['name'=>'Isi nama bebas atau pilih pegawai.'])->withInput();
        }

        $col = $this->rosterDateCol();

        // payload sesuai skema DB
        $payload = [
            'user_id'     => $data['user_id'] ?? null,
            'shift'       => $data['shift'] ?? 'pagi',
            'catatan'     => $data['note'] ?? null,   // map ke kolom 'catatan'
            'assigned_by' => auth()->id(),
        ];
        if (Schema::hasColumn('piket_rosters','name')) {
            $payload['name'] = $data['name'] ?? null;
        }

        $row = PiketRoster::updateOrCreate(
            [$col => $data['date']],   // kunci unik tanggal pakai kolom dinamis
            $payload
        );

        // buat alias agar flash message tidak error walau cast bukan 'date'
        $tanggal = $row->{$col} instanceof \Carbon\Carbon
            ? $row->{$col}->translatedFormat('d M Y')
            : (string) $row->{$col};

        return back()->with('success','Roster tersimpan untuk '.$tanggal.'.');
    }

    public function rosterDestroy(PiketRoster $roster)
    {
        $col = $this->rosterDateCol();
        $tanggal = $roster->{$col} instanceof \Carbon\Carbon
            ? $roster->{$col}->translatedFormat('d M Y')
            : (string) $roster->{$col};

        $roster->delete();
        return back()->with('success',"Roster {$tanggal} dihapus.");
    }
}
