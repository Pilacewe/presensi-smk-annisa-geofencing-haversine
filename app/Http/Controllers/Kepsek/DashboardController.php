<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // ===== Total Pegawai (role guru) =====
        $totalPegawai = User::where('role', 'guru')->count();

        // ===== Ringkasan hari ini + AVG telat =====
        $ringkas = DB::table('presensis')
            ->selectRaw("
                SUM(CASE WHEN status='hadir' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN status='telat' THEN 1 ELSE 0 END) AS telat,
                SUM(CASE WHEN status='sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN status='izin'  THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN status='alpha' THEN 1 ELSE 0 END) AS alpha,
                AVG(NULLIF(telat_menit,0)) AS avg_telat
            ")
            ->whereDate('tanggal', $today)
            ->first();

        // ===== Notifikasi ringkas (harian) counts =====
        $sudahCheckin = (int) DB::table('presensis')
            ->whereDate('tanggal', $today)
            ->where(function ($q) {
                $q->whereIn('status', ['hadir', 'telat'])
                  ->orWhereNotNull('jam_masuk');
            })
            ->distinct('user_id')->count('user_id');

        $belumPresensiCount = max($totalPegawai - $sudahCheckin, 0);

        $belumCheckout = (int) DB::table('presensis')
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_keluar')
            ->count();

        $izinPending = (int) DB::table('izins')
            ->whereIn('status', ['pending', 'menunggu'])
            ->count();

        // ===== Aktivitas terbaru (hari ini) - ambil sampai 50 untuk fleksibilitas view =====
        $latest = DB::table('presensis as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->whereDate('p.tanggal', $today)
            ->orderByDesc('p.id')
            ->limit(50)
            ->get([
                'u.id','u.name','u.jabatan',
                'p.status', 'p.jam_masuk', 'p.jam_keluar', 'p.telat_menit'
            ]);

        // ===== Roster piket (opsional) =====
        $piketQuery = DB::table('piket_rosters as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->whereDate('r.tanggal', $today);

        if (Schema::hasColumn('piket_rosters', 'shift')) {
            $piketQuery->orderBy('r.shift');
            $piket = $piketQuery->get(['u.name','u.jabatan','r.tanggal','r.shift']);
        } else {
            $piket = $piketQuery->get(['u.name','u.jabatan','r.tanggal']);
        }

        // ===== Top telat 7 hari (sidebar) =====
        $startTop = $today->copy()->subDays(6);
        $topTelat = DB::table('presensis as p')
            ->join('users as u','u.id','=','p.user_id')
            ->whereBetween('p.tanggal', [$startTop->toDateString(), $today->toDateString()])
            ->groupBy('u.id','u.name','u.jabatan')
            ->orderByDesc(DB::raw('SUM(COALESCE(p.telat_menit,0))'))
            ->limit(5)
            ->get([
                'u.id','u.name','u.jabatan',
                DB::raw('SUM(COALESCE(p.telat_menit,0)) as total_telat')
            ]);

        /* ============================
         * Koleksi daftar per status hari ini
         * ============================ */

        // Semua guru (id + name + jabatan)
        $allGuru = DB::table('users')->where('role','guru')->get(['id','name','jabatan']);

        // Ambil presensi hari ini per user (jika ada)
        $presToday = DB::table('presensis')
            ->whereDate('tanggal', $today)
            ->get(['user_id','status','jam_masuk','jam_keluar','telat_menit'])
            ->keyBy('user_id'); // key by user_id untuk lookup cepat

        // 1) Belum Masuk: guru yang tidak punya record atau jam_masuk null
        $belumMasuk = $allGuru->filter(function($g) use ($presToday) {
            if (!isset($presToday[$g->id])) return true; // tidak ada record → belum masuk
            $rec = $presToday[$g->id];
            return (empty($rec->jam_masuk) && $rec->status !== 'hadir' && $rec->status !== 'telat');
        })->values();

        // 2) Sudah Hadir (status = hadir)
        $sudahHadirList = $allGuru->filter(function($g) use ($presToday) {
            return isset($presToday[$g->id]) && ($presToday[$g->id]->status === 'hadir');
        })->values();

        // 3) Telat (status = telat)
        $telatList = $allGuru->filter(function($g) use ($presToday) {
            return isset($presToday[$g->id]) && ($presToday[$g->id]->status === 'telat');
        })->values();

        // 4) Izin / Sakit (status = izin OR sakit)
        $izinList = $allGuru->filter(function($g) use ($presToday) {
            return isset($presToday[$g->id]) && in_array($presToday[$g->id]->status, ['izin','sakit']);
        })->values();

        // ===== Fitur baru produktivitas (tetap kirim, bila diperlukan di view) =====
        $startMonth = $today->copy()->startOfMonth();

        $presentMtd = (int) DB::table('presensis')
            ->whereBetween('tanggal', [$startMonth->toDateString(), $today->toDateString()])
            ->where('status', 'hadir')
            ->count();

        $lateMtd = (int) DB::table('presensis')
            ->whereBetween('tanggal', [$startMonth->toDateString(), $today->toDateString()])
            ->where('status', 'telat')
            ->count();

        $ontimeRateMtd = ($presentMtd + $lateMtd) > 0
            ? round($presentMtd / ($presentMtd + $lateMtd) * 100, 1)
            : 0.0;

        // return view with all prepared data
        return view('kepsek.dashboard', [
            'today'             => $today,
            'totalPegawai'      => $totalPegawai,
            'ringkas'           => $ringkas,
            'belumPresensi'     => $belumPresensiCount,
            'belumCheckout'     => $belumCheckout,
            'izinPending'       => $izinPending,
            'latest'            => $latest,
            'piket'             => $piket,
            'topTelat'          => $topTelat,

            // daftar per status (untuk Blade)
            'belumMasuk'        => $belumMasuk,
            'sudahHadirList'    => $sudahHadirList,
            'telatList'         => $telatList,
            'izinList'          => $izinList,

            // produktivitas (opsional)
            'ontimeRateMtd'     => $ontimeRateMtd,
        ]);
    }
}
