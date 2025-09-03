<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Presensi;
use App\Models\Izin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminTuController extends Controller
{
    /** Dashboard kecil + list TU */
    public function index(Request $r)
    {
        $q      = trim((string) $r->get('q', ''));
        $active = $r->get('active'); // '1' | '0' | null

        $items = User::query()
            ->where('role', 'tu')
            ->when($q, function($x) use ($q){
                $x->where(function($w) use ($q){
                    $w->where('name','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%")
                      ->orWhere('jabatan','like',"%{$q}%");
                });
            })
            ->when($active !== null && $active !== '', fn($x)=>$x->where('is_active', (int)$active))
            ->orderBy('name')
            ->paginate(12)->withQueryString();

        // Ringkasan akun
        $summary = [
            'total'    => User::where('role','tu')->count(),
            'aktif'    => User::where('role','tu')->where('is_active',1)->count(),
            'nonaktif' => User::where('role','tu')->where('is_active',0)->count(),
        ];

        // Hari ini (kehadiran TU)
        $today = now(config('app.timezone'))->toDateString();
        $tuIds = User::where('role','tu')->pluck('id');

        $presentIds = Presensi::whereIn('user_id',$tuIds)
                        ->whereDate('tanggal',$today)
                        ->whereIn('status',['hadir','telat'])
                        ->pluck('user_id')->unique();

        $izinIds = Izin::where('status','approved')->whereIn('user_id',$tuIds)
                    ->whereDate('tgl_mulai','<=',$today)
                    ->whereDate('tgl_selesai','>=',$today)
                    ->pluck('user_id')->unique();

        $todayStats = [
            'hadir' => Presensi::whereIn('user_id',$tuIds)->whereDate('tanggal',$today)->where('status','hadir')->count(),
            'telat' => Presensi::whereIn('user_id',$tuIds)->whereDate('tanggal',$today)->where('status','telat')->count(),
            'izin'  => Izin::where('status','approved')->where('jenis','izin')->whereIn('user_id',$tuIds)
                           ->whereDate('tgl_mulai','<=',$today)->whereDate('tgl_selesai','>=',$today)->count(),
            'sakit' => Izin::where('status','approved')->where('jenis','sakit')->whereIn('user_id',$tuIds)
                           ->whereDate('tgl_mulai','<=',$today)->whereDate('tgl_selesai','>=',$today)->count(),
            'belum' => max(User::where('role','tu')->count() - $presentIds->merge($izinIds)->unique()->count(), 0),
        ];

        // Pending izin (TU saja)
        $pendingIzin = Izin::with('user:id,name')
            ->whereIn('user_id',$tuIds)
            ->where('status','pending')
            ->whereDate('tgl_selesai','>=',$today)
            ->orderByDesc('created_at')
            ->take(6)->get();

        // Leaderboard bulan ini (hadir & telat)
        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();

        $leaderboardHadir = Presensi::select('user_id', DB::raw('COUNT(*) as jml'))
            ->whereIn('user_id',$tuIds)
            ->whereBetween('tanggal',[$start,$end])
            ->whereIn('status',['hadir','telat'])
            ->groupBy('user_id')
            ->with('user:id,name')->orderByDesc('jml')->take(6)->get();

        $leaderboardTelat = Presensi::select('user_id',
                DB::raw("COUNT(CASE WHEN status='telat' THEN 1 END) as jml"),
                DB::raw("SUM(COALESCE(telat_menit,0)) as menit")
            )
            ->whereIn('user_id',$tuIds)
            ->whereBetween('tanggal',[$start,$end])
            ->groupBy('user_id')->with('user:id,name')
            ->orderByDesc('jml')->take(6)->get();

        // Terbaru
        $recentUsers = User::where('role','tu')->latest()->take(6)->get();

        return view('admin.tu.index', compact(
            'items','summary','todayStats','pendingIzin',
            'leaderboardHadir','leaderboardTelat','recentUsers','q','active'
        ));
    }

    public function create()
    {
        return view('admin.tu.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'      => ['required','string','max:120'],
            'email'     => ['required','email','max:160', Rule::unique('users','email')],
            'jabatan'   => ['nullable','string','max:120'],
            'is_active' => ['required','boolean'],
            'password'  => ['nullable','string','min:6'],
            'avatar'    => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);

        $path = null;
        if ($r->hasFile('avatar')) {
            $path = $r->file('avatar')->store('avatars','public');
        }

        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'role'        => 'tu',
            'jabatan'     => $data['jabatan'] ?? null,
            'is_active'   => (int) $data['is_active'],
            'password'    => Hash::make($data['password'] ?? '12345678'),
            'avatar_path' => $path,
        ]);

        return redirect()->route('admin.tu.index')->with('success','Akun TU berhasil ditambahkan.');
    }

    public function show(User $user)
    {
        abort_unless($user->role === 'tu', 404);

        $tz    = config('app.timezone');
        $today = now($tz)->toDateString();
        $monthStart = now($tz)->startOfMonth()->toDateString();
        $monthEnd   = now($tz)->endOfMonth()->toDateString();

        $hariIni = Presensi::where('user_id',$user->id)->whereDate('tanggal',$today)->first();

        $rekap = [
            'hadir' => Presensi::where('user_id',$user->id)->whereBetween('tanggal',[$monthStart,$monthEnd])->where('status','hadir')->count(),
            'telat' => Presensi::where('user_id',$user->id)->whereBetween('tanggal',[$monthStart,$monthEnd])->where('status','telat')->count(),
            'izin'  => Izin::where('user_id',$user->id)->where('status','approved')
                           ->whereBetween('tgl_mulai',[$monthStart,$monthEnd])->count(),
            'sakit' => Izin::where('user_id',$user->id)->where('status','approved')
                           ->where('jenis','sakit')
                           ->whereBetween('tgl_mulai',[$monthStart,$monthEnd])->count(),
        ];

        $riwayat = Presensi::where('user_id',$user->id)->latest('tanggal')->take(20)->get();

        return view('admin.tu.show', compact('user','hariIni','rekap','riwayat'));
    }

    public function edit(User $user)
    {
        abort_unless($user->role === 'tu', 404);
        return view('admin.tu.edit', compact('user'));
    }

    public function update(Request $r, User $user)
    {
        abort_unless($user->role === 'tu', 404);

        $data = $r->validate([
            'name'      => ['required','string','max:120'],
            'email'     => ['required','email','max:160', Rule::unique('users','email')->ignore($user->id)],
            'jabatan'   => ['nullable','string','max:120'],
            'is_active' => ['required','boolean'],
            'password'  => ['nullable','string','min:6'],
            'avatar'    => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);

        if ($r->filled('password')) {
            $user->password = Hash::make($data['password']);
        }

        if ($r->hasFile('avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $r->file('avatar')->store('avatars','public');
        }

        $user->fill([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'jabatan'   => $data['jabatan'] ?? null,
            'is_active' => (int) $data['is_active'],
        ])->save();

        return redirect()->route('admin.tu.index')->with('success','Akun TU diperbarui.');
    }

    public function destroy(User $user)
    {
        abort_unless($user->role === 'tu', 404);

        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }
        $user->delete();
        return back()->with('success','Akun TU dihapus.');
    }

    public function resetPassword(User $user)
    {
        abort_unless($user->role === 'tu', 404);
        $user->update(['password' => Hash::make('12345678')]);
        return back()->with('success', 'Password direset ke: 12345678');
    }

    /** ====== Import / Export CSV sederhana ====== */

    // EXPORT: name,email,jabatan,is_active
    public function export()
    {
        $rows = User::where('role','tu')->orderBy('name')->get(['name','email','jabatan','is_active']);
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tu_export_'.date('Ymd_His').'.csv"',
        ];
        $callback = function() use ($rows){
            $out = fopen('php://output','w');
            fputcsv($out, ['name','email','jabatan','is_active']);
            foreach($rows as $r){
                fputcsv($out, [$r->name,$r->email,$r->jabatan,$r->is_active]);
            }
            fclose($out);
        };
        return Response::stream($callback, 200, $headers);
    }

    // IMPORT: kolom wajib -> name,email ; opsional -> jabatan,is_active,password
    public function import(Request $r)
    {
        $r->validate(['file'=>'required|file|mimes:csv,txt|max:2048']);
        $fh = fopen($r->file('file')->getRealPath(), 'r');
        $header = fgetcsv($fh);
        $map = collect($header)->mapWithKeys(fn($h,$i)=>[strtolower(trim($h))=>$i])->all();

        $required = ['name','email'];
        foreach($required as $col){
            if(!array_key_exists($col,$map)){
                return back()->withErrors(['file'=>"Kolom '{$col}' wajib ada."])->withInput();
            }
        }

        $inserted = 0; $skipped = 0;
        while(($row = fgetcsv($fh)) !== false){
            $email = trim($row[$map['email']] ?? '');
            $name  = trim($row[$map['name']]  ?? '');
            if(!$email || !$name){ $skipped++; continue; }

            if(User::where('email',$email)->exists()){ $skipped++; continue; }

            User::create([
                'name'      => $name,
                'email'     => $email,
                'role'      => 'tu',
                'jabatan'   => trim($row[$map['jabatan']]   ?? '') ?: null,
                'is_active' => (int) trim($row[$map['is_active']] ?? 1),
                'password'  => Hash::make(trim($row[$map['password']] ?? '12345678')),
            ]);
            $inserted++;
        }
        fclose($fh);

        return back()->with('success',"Import selesai. Tambah: {$inserted}, Lewati: {$skipped}.");
    }
}
    