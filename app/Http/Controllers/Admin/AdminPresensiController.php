<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AdminPresensiController extends Controller
{
    /**
     * List & filter presensi — khusus role guru & tu.
     */
    public function index(Request $request)
    {
        // ===== Role yang diperbolehkan di halaman ini
        $allowedRoles = ['guru', 'tu'];

        // ===== Param filter
        $q      = trim((string) $request->get('q', ''));
        $role   = $request->get('role');     // guru|tu|null
        $status = $request->get('status');   // hadir|telat|izin|sakit|alfa|null
        $start  = $request->get('start');    // yyyy-mm-dd
        $end    = $request->get('end');      // yyyy-mm-dd

        // Default rentang tanggal: 7 hari terakhir s.d. hari ini
        $defStart = Carbon::now()->subDays(7)->toDateString();
        $defEnd   = Carbon::now()->toDateString();
        $start    = $start ?: $defStart;
        $end      = $end   ?: $defEnd;
        if ($end < $start) $end = $start; // normalisasi

        // Validasi nilai role & status yang masuk
        if ($role && !in_array($role, $allowedRoles, true))   { $role = null; }
        $validStatus = ['hadir','telat','izin','sakit','alfa'];
        if ($status && !in_array($status, $validStatus, true)){ $status = null; }

        // ===== Query utama: batasi *selalu* ke user guru|tu
        $items = Presensi::with(['user:id,name,role'])
            ->whereBetween('tanggal', [$start, $end])
            ->whereHas('user', function ($u) use ($allowedRoles, $role) {
                $u->whereIn('role', $allowedRoles);
                if ($role) $u->where('role', $role);
            })
            ->when($q, function($qq) use ($q){
                $qq->whereHas('user', function($u) use ($q){
                    $u->where('name','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%");
                });
            })
            ->when($status, fn($qs)=>$qs->where('status',$status))
            ->orderByDesc('tanggal')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        // ===== Ringkasan dengan constraint yang sama
        $baseSummary = Presensi::query()
            ->whereBetween('tanggal', [$start, $end])
            ->whereHas('user', function ($u) use ($allowedRoles, $role) {
                $u->whereIn('role', $allowedRoles);
                if ($role) $u->where('role', $role);
            })
            ->select([
                DB::raw("SUM(CASE WHEN status='hadir' THEN 1 ELSE 0 END) as s_hadir"),
                DB::raw("SUM(CASE WHEN status='telat' THEN 1 ELSE 0 END) as s_telat"),
                DB::raw("SUM(CASE WHEN status='izin'  THEN 1 ELSE 0 END) as s_izin"),
                DB::raw("SUM(CASE WHEN status='sakit' THEN 1 ELSE 0 END) as s_sakit"),
                DB::raw("SUM(CASE WHEN status='alfa'  THEN 1 ELSE 0 END) as s_alfa"),
            ])->first();

        $ringkas = [
            // Hadir = hadir + telat (lebih masuk akal untuk KPI kehadiran)
            'hadir' => (int)($baseSummary->s_hadir ?? 0) + (int)($baseSummary->s_telat ?? 0),
            'telat' => (int)($baseSummary->s_telat ?? 0), // tersedia jika mau dipakai di UI nanti
            'izin'  => (int)($baseSummary->s_izin  ?? 0),
            'sakit' => (int)($baseSummary->s_sakit ?? 0),
            'alfa'  => (int)($baseSummary->s_alfa  ?? 0),
        ];

        return view('admin.presensi.index', [
            'items'  => $items,     // paginator
            'ringkas'=> $ringkas,
            'q'      => $q,
            'role'   => $role,
            'status' => $status,
            'start'  => $start,
            'end'    => $end,
            // default untuk isi form
            'defStart' => $defStart,
            'defEnd'   => $defEnd,
            // kalau perlu dropdown role di blade
            'allowedRoles' => $allowedRoles,
        ]);
    }

    public function create()
    {
        // User dropdown dibatasi ke guru & tu saja
        $users = User::whereIn('role',['guru','tu'])
            ->orderBy('name')
            ->get(['id','name','role']);
        return view('admin.presensi.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'    => ['required', Rule::exists('users','id')],
            'tanggal'    => ['required','date'],
            'status'     => ['required', Rule::in(['hadir','telat','izin','sakit','alfa'])],
            'jam_masuk'  => ['nullable','date_format:H:i'],
            'jam_keluar' => ['nullable','date_format:H:i','after_or_equal:jam_masuk'],
            'keterangan' => ['nullable','string','max:500'],
        ]);

        // Cegah duplikat per user+tanggal
        $exists = Presensi::where('user_id',$data['user_id'])
                          ->whereDate('tanggal',$data['tanggal'])->exists();
        if ($exists) {
            return back()->withErrors(['tanggal'=>'Sudah ada presensi untuk user & tanggal ini.'])->withInput();
        }

        Presensi::create($data);
        return redirect()->route('admin.presensi.index')->with('success','Data presensi ditambahkan.');
    }

    public function edit(Presensi $presensi)
    {
        $users = User::whereIn('role',['guru','tu'])
            ->orderBy('name')
            ->get(['id','name','role']);
        return view('admin.presensi.edit', compact('presensi','users'));
    }

    public function update(Request $request, Presensi $presensi)
    {
        $data = $request->validate([
            'user_id'    => ['required', Rule::exists('users','id')],
            'tanggal'    => ['required','date'],
            'status'     => ['required', Rule::in(['hadir','telat','izin','sakit','alfa'])],
            'jam_masuk'  => ['nullable','date_format:H:i'],
            'jam_keluar' => ['nullable','date_format:H:i','after_or_equal:jam_masuk'],
            'keterangan' => ['nullable','string','max:500'],
        ]);

        // Cegah duplikat saat update
        $exists = Presensi::where('user_id',$data['user_id'])
                          ->whereDate('tanggal',$data['tanggal'])
                          ->where('id','!=',$presensi->id)
                          ->exists();
        if ($exists) {
            return back()->withErrors(['tanggal'=>'Sudah ada presensi untuk user & tanggal ini.'])->withInput();
        }

        $presensi->update($data);
        return redirect()->route('admin.presensi.index')->with('success','Data presensi diperbarui.');
    }

    public function destroy(Presensi $presensi)
    {
        $presensi->delete();
        return redirect()->route('admin.presensi.index')->with('success','Data presensi dihapus.');
    }
}
