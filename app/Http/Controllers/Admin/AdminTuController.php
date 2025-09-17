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

        /* ===========================================================
         * === DATASET VISUALISASI (STACKED BAR, gaya halaman guru) ===
         * - Periode: bulan berjalan s.d. hari ini
         * - “Belum Absen” dihitung per hari kerja (Sen–Jum)
         *   sejak max(created_at, awal bulan) hingga hari ini,
         *   dikurangi hari yang sudah tercatat (Hadir/Telat)
         *   atau tertutup Izin/Sakit (approved).
         * ===========================================================
         */
        $tz         = config('app.timezone', 'Asia/Jakarta');
        $todayCal   = Carbon::now($tz)->toDateString();
        $monthStart = Carbon::now($tz)->startOfMonth()->toDateString();

        // ====== Kesimpulan (TOP 5) ======
// Paling rajin = jumlah HADIR terbanyak pada bulan berjalan
$topRajinTU = \App\Models\Presensi::with('user:id,name')
    ->whereIn('user_id', $tuIds)
    ->whereBetween('tanggal', [$monthStart, $todayCal])
    ->select(
        'user_id',
        \DB::raw("SUM(CASE WHEN status='hadir' THEN 1 ELSE 0 END) as jml_hadir")
    )
    ->groupBy('user_id')
    ->orderByDesc('jml_hadir')
    ->limit(5)
    ->get();

// Paling sering telat = jumlah TELAT terbanyak + total menit telat
$topTelatTU = \App\Models\Presensi::with('user:id,name')
    ->whereIn('user_id', $tuIds)
    ->whereBetween('tanggal', [$monthStart, $todayCal])
    ->where('status', 'telat')
    ->select(
        'user_id',
        \DB::raw('COUNT(*) as jml_telat'),
        \DB::raw('SUM(COALESCE(telat_menit,0)) as total_menit')
    )
    ->groupBy('user_id')
    ->orderByDesc('jml_telat')
    ->limit(5)
    ->get();

        // Semua TU (dengan created_at untuk baseline per-user)
        $tuUsers = User::where('role','tu')
            ->orderBy('name')
            ->get(['id','name','created_at']);

        // === Presensi bulan ini (hadir/telat) -> map per user per tanggal
$presRows = Presensi::whereBetween('tanggal', [$monthStart, $todayCal])
    ->whereIn('user_id', $tuUsers->pluck('id'))
    ->get(['user_id','tanggal','status','telat_menit']);

$presMap  = [];  // $presMap[uid][Y-m-d] = status
$hadirAgg = [];  // total hadir per user (bulan ini)
$telatAgg = [];  // total telat per user (bulan ini)

foreach ($presRows as $pr) {
    $uid = (int) $pr->user_id;

    // pastikan key tanggal berupa string 'Y-m-d', bukan objek Carbon
    $tgl = $pr->tanggal instanceof \DateTimeInterface
        ? $pr->tanggal->format('Y-m-d')
        : \Illuminate\Support\Carbon::parse($pr->tanggal)->toDateString();

    // inisialisasi slot user
    if (!isset($presMap[$uid])) $presMap[$uid] = [];

    // tandai presensi tercatat di tanggal tsb.
    $presMap[$uid][$tgl] = $pr->status;

    if ($pr->status === 'hadir') {
        $hadirAgg[$uid] = ($hadirAgg[$uid] ?? 0) + 1;
    } elseif ($pr->status === 'telat') {
        $telatAgg[$uid] = ($telatAgg[$uid] ?? 0) + 1;
    }
}

        // Izin/Sakit approved yang overlap bulan ini
        $izinRows = Izin::where('status','approved')
            ->whereIn('user_id', $tuUsers->pluck('id'))
            ->where(function($q) use ($monthStart,$todayCal){
                $q->whereBetween('tgl_mulai',   [$monthStart,$todayCal])
                  ->orWhereBetween('tgl_selesai',[$monthStart,$todayCal])
                  ->orWhere(function($qq) use($monthStart,$todayCal){
                      $qq->where('tgl_mulai','<=',$monthStart)->where('tgl_selesai','>=',$todayCal);
                  });
            })
            ->get(['user_id','jenis','tgl_mulai','tgl_selesai']); // jenis: izin|sakit

        // Expand ke per-hari kerja (Sen–Jum) agar konsisten dengan “Belum”
        $izinDays  = [];  // $izinDays[uid][Y-m-d] = 'izin'|'sakit'
        foreach ($izinRows as $iz) {
            $uid   = (int)$iz->user_id;
            $startD = Carbon::parse(max($iz->tgl_mulai, $monthStart));
            $endD   = Carbon::parse(min($iz->tgl_selesai, $todayCal));
            for ($d = $startD->copy(); $d->lte($endD); $d->addDay()) {
                if (in_array($d->dayOfWeekIso, [6,7])) continue; // skip Sabtu/Minggu
                $izinDays[$uid][$d->toDateString()] = $iz->jenis; // izin/sakit
            }
        }

        // Susun dataset chart
        $chartLabels = [];
        $chartHadir  = [];
        $chartTelat  = [];
        $chartIzin   = [];
        $chartSakit  = [];
        $chartBelum  = [];

        foreach ($tuUsers as $u) {
            $chartLabels[] = $u->name;
            $uid = (int)$u->id;

            // baseline mulai hitung: max(created_at, awal bulan)
            $userStart = Carbon::parse($u->created_at)->startOfDay()->toDateString();
            $startCnt  = Carbon::parse(max($userStart, $monthStart));
            $endCnt    = Carbon::parse($todayCal);

            $workdays = 0; $izinHari = 0; $sakitHari = 0; $tercatat = 0;

            for ($d = $startCnt->copy(); $d->lte($endCnt); $d->addDay()) {
                if (in_array($d->dayOfWeekIso, [6,7])) continue; // hanya hari kerja
                $tgl = $d->toDateString();
                $workdays++;

                if (!empty($presMap[$uid][$tgl])) { // Hadir/Telat tercatat
                    $tercatat++;
                    continue;
                }
                if (!empty($izinDays[$uid][$tgl])) {
                    if ($izinDays[$uid][$tgl] === 'sakit') $sakitHari++;
                    else $izinHari++;
                }
            }

            $h  = (int)($hadirAgg[$uid] ?? 0);
            $t  = (int)($telatAgg[$uid] ?? 0);
            $iz = (int)$izinHari;
            $sa = (int)$sakitHari;
            $bl = max($workdays - ($h + $t + $iz + $sa), 0);

            $chartHadir[] = $h;
            $chartTelat[] = $t;
            $chartIzin[]  = $iz;
            $chartSakit[] = $sa;
            $chartBelum[] = $bl;
        }

        $chartPeriod = Carbon::now($tz)->translatedFormat('F Y');

        return view('admin.tu.index', compact(
            'items','summary','todayStats','pendingIzin',
            'leaderboardHadir','leaderboardTelat','recentUsers','q','active',
            // === kirim ke view untuk kesimpulan & chart ===
            'chartLabels','chartHadir','chartTelat','chartIzin','chartSakit','chartBelum','chartPeriod',
            'topRajinTU','topTelatTU'
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
