<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Presensi;
use App\Models\Izin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $tz    = config('app.timezone', 'Asia/Jakarta');
        $now   = Carbon::now($tz);
        $today = $now->toDateString();

        /* ================= Komposisi akun per role ================= */
        $komposisi = [
            'guru'   => User::where('role','guru')->count(),
            'tu'     => User::where('role','tu')->count(),
            'piket'  => User::where('role','piket')->count(),
            'kepsek' => User::where('role','kepsek')->count(),
        ];
        // alias lama untuk kompat laman lama
        $roleCounts = $komposisi;

        /* ================= Definisi WAJIB ABSEN ================= */
        $wajibIds   = User::whereIn('role', ['guru','tu'])->pluck('id');
        $totalWajib = $wajibIds->count();

        /* ================= KPI hari ini ================= */
        $kpi = [
            'hadir' => Presensi::whereDate('tanggal',$today)->where('status','hadir')->count(),
            'telat' => Presensi::whereDate('tanggal',$today)->where('status','telat')->count(),
            'izin'  => Izin::where('status','approved')->where('jenis','izin')
                           ->whereDate('tgl_mulai','<=',$today)->whereDate('tgl_selesai','>=',$today)->count(),
            'sakit' => Izin::where('status','approved')->where('jenis','sakit')
                           ->whereDate('tgl_mulai','<=',$today)->whereDate('tgl_selesai','>=',$today)->count(),
        ];

        // “Belum absen” hanya untuk yang wajib
        $presentWajibIds = Presensi::whereDate('tanggal',$today)
            ->whereIn('status',['hadir','telat'])
            ->whereIn('user_id',$wajibIds)
            ->pluck('user_id')->unique();

        $dispensasiWajibIds = Izin::where('status','approved')
            ->whereIn('jenis',['izin','sakit'])
            ->whereDate('tgl_mulai','<=',$today)
            ->whereDate('tgl_selesai','>=',$today)
            ->whereIn('user_id',$wajibIds)
            ->pluck('user_id')->unique();

        $tercover = $presentWajibIds->merge($dispensasiWajibIds)->unique();
        $kpi['belum'] = max($totalWajib - $tercover->count(), 0);

        // alias lama
        $att = [
            'hadir' => $kpi['hadir'],
            'telat' => $kpi['telat'],
            'izin'  => $kpi['izin'],
            'sakit' => $kpi['sakit'],
            'belum' => $kpi['belum'],
        ];

        /* ================= Tren 14 hari terakhir ================= */
        $startTrend = $now->copy()->subDays(13)->toDateString();
        $rangeDays  = collect(range(0,13))->map(
            fn($d)=>$now->copy()->subDays(13-$d)->toDateString()
        );

        $hadirMap = Presensi::select('tanggal',
                        DB::raw("SUM(CASE WHEN status='hadir' THEN 1 ELSE 0 END) as jml"))
                    ->whereBetween('tanggal',[$startTrend,$today])
                    ->groupBy('tanggal')
                    ->pluck('jml','tanggal')->toArray();

        $telatMap = Presensi::select('tanggal',
                        DB::raw("SUM(CASE WHEN status='telat' THEN 1 ELSE 0 END) as jml"))
                    ->whereBetween('tanggal',[$startTrend,$today])
                    ->groupBy('tanggal')
                    ->pluck('jml','tanggal')->toArray();

        $trend = [
            'labels' => $rangeDays->map(fn($d)=>Carbon::parse($d)->format('d M'))->values(),
            'hadir'  => $rangeDays->map(fn($d)=> (int)($hadirMap[$d] ?? 0))->values(),
            'telat'  => $rangeDays->map(fn($d)=> (int)($telatMap[$d] ?? 0))->values(),
        ];

        /* ================= Panel “Perlu Perhatian” ================= */
        $attention = [
            'belum'       => $kpi['belum'],
            'telat30'     => Presensi::whereDate('tanggal',$today)->where('status','telat')
                                  ->where('telat_menit','>=',30)->count(),
            'tanpaKeluar' => Presensi::whereDate('tanggal',$today)
                                  ->whereNotNull('jam_masuk')->whereNull('jam_keluar')->count(),
        ];
        // alias lama
        $belumAbsen  = $attention['belum'];
        $telat30     = $attention['telat30'];
        $tanpaKeluar = $attention['tanpaKeluar'];

        /* ================= Izin Pending ================= */
        $pending = Izin::with('user:id,name')
            ->where('status','pending')
            ->whereDate('tgl_selesai','>=',$today)
            ->orderByDesc('created_at')
            ->take(5)
            ->get(['id','user_id','jenis','tgl_mulai','tgl_selesai','created_at','keterangan']);
        $pendingIzinCount = $pending->count();
        $pendingIzinList  = $pending;

        /* ================= Ringkasan Bulan Ini ================= */
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd   = $now->copy()->endOfMonth()->toDateString();

        $monthly = Presensi::whereBetween('tanggal', [$monthStart,$monthEnd])->select([
            DB::raw("SUM(CASE WHEN status='hadir' THEN 1 ELSE 0 END) as m_hadir"),
            DB::raw("SUM(CASE WHEN status='telat' THEN 1 ELSE 0 END) as m_telat"),
            DB::raw("SUM(COALESCE(telat_menit,0)) as total_telat_menit"),
        ])->first();

        $totalKehadiran = (int)($monthly->m_hadir ?? 0) + (int)($monthly->m_telat ?? 0);
        $telatCount     = (int)($monthly->m_telat ?? 0);
        $avgTelatMenit  = $telatCount > 0
                            ? intdiv((int)$monthly->total_telat_menit, $telatCount)
                            : 0;

        $bulanIzin = Izin::where('status','approved')->where('jenis','izin')
            ->where(function($q) use ($monthStart,$monthEnd){
                $q->whereBetween('tgl_mulai',   [$monthStart,$monthEnd])
                  ->orWhereBetween('tgl_selesai',[$monthStart,$monthEnd])
                  ->orWhere(function($qq) use ($monthStart,$monthEnd){
                      $qq->where('tgl_mulai','<=',$monthStart)
                         ->where('tgl_selesai','>=',$monthEnd);
                  });
            })->count();

        $bulanSakit = Izin::where('status','approved')->where('jenis','sakit')
            ->where(function($q) use ($monthStart,$monthEnd){
                $q->whereBetween('tgl_mulai',   [$monthStart,$monthEnd])
                  ->orWhereBetween('tgl_selesai',[$monthStart,$monthEnd])
                  ->orWhere(function($qq) use ($monthStart,$monthEnd){
                      $qq->where('tgl_mulai','<=',$monthStart)
                         ->where('tgl_selesai','>=',$monthEnd);
                  });
            })->count();

        /* ================= Top Telat (7 hari terakhir) ================= */
        $weekStart = $now->copy()->subDays(6)->toDateString();
        $topTelat = Presensi::with('user:id,name')
            ->whereBetween('tanggal', [$weekStart, $today])
            ->where('status','telat')
            ->select(
                'user_id',
                DB::raw('COUNT(*) as jml'),
                DB::raw('SUM(COALESCE(telat_menit,0)) as menit')
            )
            ->groupBy('user_id')
            ->orderByDesc('jml')
            ->limit(5)
            ->get();

        /* ================= Aktivitas terbaru & belum pulang ================= */
        $latestActivities = Presensi::with('user:id,name')
            ->whereDate('tanggal', $today)
            ->orderByDesc(DB::raw('COALESCE(jam_keluar, jam_masuk, updated_at)'))
            ->limit(10)
            ->get(['id','user_id','tanggal','status','jam_masuk','jam_keluar','updated_at']);

        $tanpaPulangList = Presensi::with('user:id,name')
            ->whereDate('tanggal',$today)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_keluar')
            ->orderBy('jam_masuk')
            ->limit(10)
            ->get(['id','user_id','tanggal','jam_masuk']);

        /* ================= Info Operasional (untuk ringkasan) ================= */
        $op = [
            'mStart'      => (string) config('presensi.jam_masuk_start',  '05:00'),
            'targetMasuk' => (string) config('presensi.jam_target_masuk', '07:00'),
            'kStart'      => (string) config('presensi.jam_keluar_start', '16:00'),
            'radius'      => (float)  config('presensi.radius', 150),
        ];

        return view('admin.dashboard', [
            // untuk dashboard baru
            'today'            => $now,
            'komposisi'        => $komposisi,
            'kpi'              => $kpi,
            'trend'            => $trend,
            'attention'        => $attention,
            'pending'          => $pending,
            'topTelat'         => $topTelat,
            'latestActivities' => $latestActivities,
            'tanpaPulangList'  => $tanpaPulangList,
            'op'               => $op,

            // alias/kompat bagi blade lama
            'roleCounts'       => $roleCounts,
            'att'              => $att,
            'belumAbsen'       => $belumAbsen,
            'telat30'          => $telat30,
            'tanpaKeluar'      => $tanpaKeluar,
            'pendingIzinCount' => $pendingIzinCount,
            'pendingIzinList'  => $pendingIzinList,
            'totalKehadiran'   => $totalKehadiran,
            'avgTelatMenit'    => $avgTelatMenit,
            'bulanIzin'        => $bulanIzin,
            'bulanSakit'       => $bulanSakit,
        ]);
    }
}
