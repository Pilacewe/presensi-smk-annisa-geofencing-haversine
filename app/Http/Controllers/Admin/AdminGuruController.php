<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Presensi;
use App\Models\Izin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminGuruController extends Controller
{
    /**
     * Dashboard + kelola akun guru.
     */
    // App\Http\Controllers\Admin\AdminGuruController.php

public function index(Request $r)
{
    $tz     = config('app.timezone','Asia/Jakarta');
    $today  = \Illuminate\Support\Carbon::now($tz)->toDateString();
    $q      = trim((string)$r->input('q',''));
    $active = $r->filled('active') ? (int) $r->input('active') : null; // 1|0|null

    // ====== Daftar guru (kelola akun)
    $items = User::where('role','guru')
        ->when($q, function($x) use ($q){
            $x->where(function($s) use ($q){
                $s->where('name','like',"%{$q}%")
                  ->orWhere('email','like',"%{$q}%")
                  ->orWhere('jabatan','like',"%{$q}%");
            });
        })
        ->when(!is_null($active), fn($x)=>$x->where('is_active',$active))
        ->orderBy('name')
        ->paginate(12)
        ->withQueryString();

    // ====== Ringkasan akun
    $summary = [
        'total'    => User::where('role','guru')->count(),
        'aktif'    => User::where('role','guru')->where('is_active',1)->count(),
        'nonaktif' => User::where('role','guru')->where('is_active',0)->count(),
    ];

    // ====== Panel “Hari Ini”
    $guruIds = User::where('role','guru')->pluck('id');

    $hadirHariIni = Presensi::whereDate('tanggal',$today)
        ->whereIn('user_id',$guruIds)->where('status','hadir')->count();

    $telatHariIni = Presensi::whereDate('tanggal',$today)
        ->whereIn('user_id',$guruIds)->where('status','telat')->count();

    $izinHariIni = Izin::where('status','approved')->where('jenis','izin')
        ->whereIn('user_id',$guruIds)
        ->whereDate('tgl_mulai','<=',$today)->whereDate('tgl_selesai','>=',$today)->count();

    $sakitHariIni = Izin::where('status','approved')->where('jenis','sakit')
        ->whereIn('user_id',$guruIds)
        ->whereDate('tgl_mulai','<=',$today)->whereDate('tgl_selesai','>=',$today)->count();

    $presentIds = Presensi::whereDate('tanggal',$today)
        ->whereIn('user_id',$guruIds)
        ->whereIn('status',['hadir','telat'])
        ->pluck('user_id')->unique();

    $dispensasiIds = Izin::where('status','approved')
        ->whereIn('jenis',['izin','sakit'])
        ->whereIn('user_id',$guruIds)
        ->whereDate('tgl_mulai','<=',$today)->whereDate('tgl_selesai','>=',$today)
        ->pluck('user_id')->unique();

    $covered = $presentIds->merge($dispensasiIds)->unique();
    $belumHariIni = max($summary['total'] - $covered->count(), 0);

    $todayStats = [
        'hadir' => $hadirHariIni,
        'telat' => $telatHariIni,
        'izin'  => $izinHariIni,
        'sakit' => $sakitHariIni,
        'belum' => $belumHariIni,
    ];

    // ====== Leaderboard (bulan berjalan)
    $now    = \Illuminate\Support\Carbon::now($tz);
    $mStart = $now->copy()->startOfMonth()->toDateString();
    $mEnd   = $now->copy()->endOfMonth()->toDateString();

    $leaderboardHadir = Presensi::with('user:id,name')
        ->whereBetween('tanggal', [$mStart,$mEnd])
        ->whereIn('user_id',$guruIds)
        ->whereIn('status',['hadir','telat'])
        ->select('user_id', DB::raw('COUNT(*) as jml'))
        ->groupBy('user_id')
        ->orderByDesc('jml')
        ->limit(5)->get();

    $leaderboardTelat = Presensi::with('user:id,name')
        ->whereBetween('tanggal', [$mStart,$mEnd])
        ->whereIn('user_id',$guruIds)
        ->where('status','telat')
        ->select('user_id',
            DB::raw('COUNT(*) as jml'),
            DB::raw('SUM(COALESCE(telat_menit,0)) as menit')
        )
        ->groupBy('user_id')
        ->orderByDesc('jml')
        ->limit(5)->get();

    $pendingIzin = Izin::with('user:id,name')
        ->whereIn('user_id',$guruIds)
        ->where('status','pending')
        ->orderByDesc('created_at')
        ->limit(5)->get(['id','user_id','jenis','tgl_mulai','tgl_selesai','keterangan','created_at']);

    $recentUsers = User::where('role','guru')
        ->orderByDesc('created_at')
        ->limit(5)
        ->get(['id','name','email','created_at','is_active']);

    /* =======================
     * Rekap bulanan untuk chart
     * ======================= */
    $allGuru = User::where('role','guru')->orderBy('name')->get(['id','name','created_at']);

    // Agg presensi bulan ini
    $presAgg = Presensi::whereBetween('tanggal', [$mStart,$mEnd])
        ->whereIn('user_id', $allGuru->pluck('id'))
        ->select(
            'user_id',
            DB::raw("SUM(CASE WHEN status='hadir' THEN 1 ELSE 0 END) as hadir"),
            DB::raw("SUM(CASE WHEN status='telat' THEN 1 ELSE 0 END) as telat"),
            DB::raw("SUM(CASE WHEN status='izin'  THEN 1 ELSE 0 END) as izin"),
            DB::raw("SUM(CASE WHEN status='sakit' THEN 1 ELSE 0 END) as sakit")
        )
        ->groupBy('user_id')
        ->get()->keyBy('user_id');

    // Hari kerja berjalan (Senin–Jumat) antara max(created_at, startOfMonth) s.d. hari ini
    $chartPeriod = $now->translatedFormat('F Y');
    $calcBelum = function(User $g) use($now,$mStart){
        $start = \Illuminate\Support\Carbon::parse($g->created_at)->gt($mStart)
            ? \Illuminate\Support\Carbon::parse($g->created_at)->startOfDay()
            : \Illuminate\Support\Carbon::parse($mStart)->startOfDay();
        $end   = $now->copy()->endOfDay();

        $workdays = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (!in_array($cursor->dayOfWeekIso,[6,7])) $workdays++;
            $cursor->addDay();
        }
        return $workdays;
    };

    $rows = collect();
    foreach ($allGuru as $g) {
        $agg  = $presAgg[$g->id] ?? null;
        $h    = (int)($agg->hadir ?? 0);
        $t    = (int)($agg->telat ?? 0);
        $iz   = (int)($agg->izin  ?? 0);
        $sk   = (int)($agg->sakit ?? 0);
        $hariKerjaBerjalan = $calcBelum($g);
        $belum = max($hariKerjaBerjalan - ($h + $t + $iz + $sk), 0);

        $rows->push([
            'name'  => $g->name,
            'hadir' => $h,
            'telat' => $t,
            'izin'  => $iz,
            'sakit' => $sk,
            'belum' => $belum,
        ]);
    }

    // ====== Kontrol tampilan chart (Top N / Semua, sort)
    $view   = $r->query('view','top');         // top|all
    $limit  = (int) $r->query('n', 12);        // untuk 'top'
    $sortBy = $r->query('sort','belum');       // belum|telat|hadir|nama
    $dir    = strtolower($r->query('dir','desc'))==='asc' ? 'asc' : 'desc';

    $sortKey = match($sortBy){
        'telat' => 'telat',
        'hadir' => 'hadir',
        'nama'  => 'name',
        default => 'belum'
    };
    $rows = $rows->sortBy($sortKey, SORT_REGULAR, $dir==='desc')->values();
    if ($view !== 'all') $rows = $rows->take($limit)->values();

    $chartLabels = $rows->pluck('name');
    $chartHadir  = $rows->pluck('hadir');
    $chartTelat  = $rows->pluck('telat');
    $chartIzin   = $rows->pluck('izin');
    $chartSakit  = $rows->pluck('sakit');
    $chartBelum  = $rows->pluck('belum');

    // Lebar kanvas minimal (buat scroll x saat "Semua")
    $chartCanvasWidth = max($chartLabels->count() * 72, 900);

    // Top list (untuk kartu ringkasan)
    $topRajin = $rows->sortByDesc('hadir')->take(5)->values();
    $topTelat = $rows->sortByDesc('telat')->take(5)->values();

    return view('admin.guru.index', compact(
        'items','q','active','summary','todayStats',
        'leaderboardHadir','leaderboardTelat','pendingIzin','recentUsers',
        'chartLabels','chartHadir','chartTelat','chartIzin','chartSakit','chartBelum',
        'chartPeriod','chartCanvasWidth','view','limit','sortBy','dir',
        'topRajin','topTelat'
    ));
}


    public function create()
    {
        return view('admin.guru.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'     => ['required','string','max:120'],
            'email'    => ['required','email','max:120','unique:users,email'],
            'password' => ['nullable','string','min:6'],
            'jabatan'  => ['nullable','string','max:120'],
            'is_active'=> ['nullable','boolean'],
        ]);

        $data['role']      = 'guru';
        $data['is_active'] = (int)($data['is_active'] ?? 1);
        $data['password']  = Hash::make($data['password'] ?? Str::random(8));

        User::create($data);
        return redirect()->route('admin.guru.index')->with('success','Guru berhasil dibuat.');
    }

    public function edit(User $user)
    {
        abort_unless($user->role==='guru', 404);
        return view('admin.guru.edit', compact('user'));
    }

    public function update(Request $r, User $user)
    {
        abort_unless($user->role==='guru', 404);

        $data = $r->validate([
            'name'     => ['required','string','max:120'],
            'email'    => ['required','email','max:120', Rule::unique('users','email')->ignore($user->id)],
            'jabatan'  => ['nullable','string','max:120'],
            'is_active'=> ['nullable','boolean'],
            'password' => ['nullable','string','min:6'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = (int)($data['is_active'] ?? $user->is_active);
        $user->update($data);

        return redirect()->route('admin.guru.index')->with('success','Data guru diperbarui.');
    }

    public function destroy(User $user)
    {
        abort_unless($user->role==='guru', 404);
        $user->delete();
        return back()->with('success','Guru dihapus.');
    }

    public function resetPassword(User $user)
    {
        abort_unless($user->role==='guru', 404);
        $new = Str::random(8);
        $user->update(['password' => Hash::make($new)]);
        return back()->with('success','Password direset: '.$new);
    }

    public function show(User $user)
    {
        if (($user->role ?? '') !== 'guru') {
            // abort(404);
        }

        $tz    = config('app.timezone', 'Asia/Jakarta');
        $today = Carbon::now($tz)->toDateString();

        $todayPresensi = Presensi::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        $todayStatusKey = $todayPresensi?->status ?? null; // hadir|telat|izin|sakit|null
        $todayIn  = $todayPresensi?->jam_masuk;
        $todayOut = $todayPresensi?->jam_keluar;

        $from7 = Carbon::now($tz)->subDays(6)->toDateString();
        $to7   = $today;

        $weekly = Presensi::where('user_id', $user->id)
            ->whereBetween('tanggal', [$from7, $to7])
            ->select([
                DB::raw("SUM(CASE WHEN status='hadir' THEN 1 ELSE 0 END) as hadir"),
                DB::raw("SUM(CASE WHEN status='telat' THEN 1 ELSE 0 END) as telat"),
                DB::raw("SUM(CASE WHEN status='izin' THEN 1 ELSE 0 END)  as izin"),
                DB::raw("SUM(CASE WHEN status='sakit' THEN 1 ELSE 0 END) as sakit"),
            ])->first();

        $mStart = Carbon::now($tz)->startOfMonth()->toDateString();
        $mEnd   = Carbon::now($tz)->endOfMonth()->toDateString();

        $monthly = Presensi::where('user_id', $user->id)
            ->whereBetween('tanggal', [$mStart, $mEnd])
            ->select([
                DB::raw("SUM(CASE WHEN status='hadir' THEN 1 ELSE 0 END) as m_hadir"),
                DB::raw("SUM(CASE WHEN status='telat' THEN 1 ELSE 0 END) as m_telat"),
                DB::raw("SUM(CASE WHEN status='izin'  THEN 1 ELSE 0 END) as m_izin"),
                DB::raw("SUM(CASE WHEN status='sakit' THEN 1 ELSE 0 END) as m_sakit"),
                DB::raw("SUM(COALESCE(telat_menit,0)) as total_telat_menit"),
            ])->first();

        $totalKehadiranBulan = (int)($monthly->m_hadir ?? 0) + (int)($monthly->m_telat ?? 0);
        $avgTelatMenit = ((int)($monthly->m_telat ?? 0) > 0)
            ? intdiv((int)$monthly->total_telat_menit, (int)$monthly->m_telat)
            : 0;

        $recentPresensi = Presensi::where('user_id', $user->id)
            ->orderByDesc('tanggal')->orderByDesc('updated_at')
            ->limit(10)->get();

        $recentIzin = Izin::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(6)->get();

        $online = $todayPresensi && $todayPresensi->jam_masuk && !$todayPresensi->jam_keluar;

        return view('admin.guru.show', [
            'u'                  => $user,
            'todayPresensi'      => $todayPresensi,
            'todayStatusKey'     => $todayStatusKey,
            'todayIn'            => $todayIn,
            'todayOut'           => $todayOut,
            'weekly'             => $weekly,
            'mStart'             => $mStart,
            'mEnd'               => $mEnd,
            'totalKehadiranBulan'=> $totalKehadiranBulan,
            'avgTelatMenit'      => $avgTelatMenit,
            'recentPresensi'     => $recentPresensi,
            'recentIzin'         => $recentIzin,
            'online'             => $online,
        ]);
    }

    // Placeholder export/import agar tombol tidak error
    public function export()
    {
        return back()->with('success','(Demo) Export CSV diproses.');
    }
    public function import(Request $r)
    {
        return back()->with('success','(Demo) Import CSV diproses.');
    }
}
