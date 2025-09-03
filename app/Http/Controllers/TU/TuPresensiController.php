<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use App\Models\Izin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;  // opsional (kalau dipakai)

class TuPresensiController extends Controller
{
    /**
     * Lihat presensi harian GURU (bukan semua role).
     * - Statistik: Hadir, Telat, Izin, Sakit, Belum (tidak minus).
     * - Baris: jika tidak ada presensi tapi ada izin/sakit approved yg overlap -> tampil izin/sakit.
     */
    public function index(Request $r)
    {
        $tz       = config('app.timezone','Asia/Jakarta');
        $tanggal  = $r->input('tanggal', Carbon::now($tz)->toDateString());
        $keyword  = trim($r->input('q',''));

        // ====== Semua GURU (populasi wajib presensi di halaman ini)
        $guruAllQ = User::where('role','guru');
        $totalGuru = (clone $guruAllQ)->count();
        $guruIdsAll = (clone $guruAllQ)->pluck('id');

        // ====== Statistik harian (GURU saja)
        $hadir = Presensi::whereDate('tanggal', $tanggal)
            ->where('status','hadir')
            ->whereIn('user_id', $guruIdsAll)
            ->count();

        $telat = Presensi::whereDate('tanggal', $tanggal)
            ->where('status','telat')
            ->whereIn('user_id', $guruIdsAll)
            ->count();

        // Izin/Sakit dari tabel IZIN (approved) yang overlap tanggal
        $izinIds = Izin::where('status','approved')
            ->where('jenis','izin')
            ->whereDate('tgl_mulai','<=',$tanggal)
            ->whereDate('tgl_selesai','>=',$tanggal)
            ->whereIn('user_id',$guruIdsAll)
            ->pluck('user_id')
            ->unique();

        $sakitIds = Izin::where('status','approved')
            ->where('jenis','sakit')
            ->whereDate('tgl_mulai','<=',$tanggal)
            ->whereDate('tgl_selesai','>=',$tanggal)
            ->whereIn('user_id',$guruIdsAll)
            ->pluck('user_id')
            ->unique();

        $izin  = $izinIds->count();
        $sakit = $sakitIds->count();

        $belum = max($totalGuru - ($hadir + $telat + $izin + $sakit), 0);

        $stat = compact('hadir','telat','izin','sakit','belum');

        // ====== Query baris: LEFT JOIN presensis (supaya semua guru tetap muncul)
        $rows = User::query()
            ->where('users.role', 'guru')
            ->when($keyword !== '', fn($q) => $q->where('users.name','like',"%{$keyword}%"))
            ->leftJoin('presensis as p', function($join) use ($tanggal){
                $join->on('p.user_id','=','users.id')
                     ->whereDate('p.tanggal',$tanggal);
            })
            ->orderBy('users.name')
            ->select([
                'users.id',
                'users.name',
                'users.jabatan',
                DB::raw('p.id as presensi_id'),
                DB::raw('p.status as status'),
                DB::raw('p.jam_masuk as jam_masuk'),
                DB::raw('p.jam_keluar as jam_keluar'),
                DB::raw('p.telat_menit as telat_menit'),
            ])
            ->paginate(20)
            ->withQueryString();

        // ====== Untuk halaman yg ter-paginate: mapping izin/sakit agar status tampil
        $pageUserIds = collect($rows->items())->pluck('id');

        $izinMap = Izin::where('status','approved')
            ->whereIn('jenis',['izin','sakit'])
            ->whereDate('tgl_mulai','<=',$tanggal)
            ->whereDate('tgl_selesai','>=',$tanggal)
            ->whereIn('user_id',$pageUserIds)
            ->orderBy('tgl_mulai','desc')
            ->get()
            ->groupBy('user_id')   // user_id => koleksi izin
            ->map(function($g){     // ambil jenis pertama untuk label
                return strtolower($g->first()->jenis);
            });

        // Tambahkan properti status izin/sakit jika presensi null
        $rows->getCollection()->transform(function($row) use ($izinMap){
            if (empty($row->status)) {
                $row->status = $izinMap->get($row->id) ?: null; // null => Belum
            }
            return $row;
        });

        return view('tu.presensi-index', [
            'rows'    => $rows,
            'tanggal' => $tanggal,
            'keyword' => $keyword,
            'stat'    => $stat,
        ]);
    }

    /**
     * Riwayat presensi (fitur lama — tetap seperti aslinya).
     */
    public function riwayat(Request $r)
{
    $tz     = config('app.timezone','Asia/Jakarta');
    $today  = now($tz);

    // ===== Filter =====
    $guruId = $r->input('guru_id');                  // optional
    $from   = $r->input('from', $today->copy()->startOfMonth()->toDateString());
    $to     = $r->input('to',   $today->copy()->endOfMonth()->toDateString());
    $status = $r->input('status');                   // optional: hadir|telat|izin|sakit

    // Dropdown guru (hanya guru untuk riwayat TU melihat guru)
    $gurus = \App\Models\User::where('role','guru')
        ->orderBy('name')->get(['id','name']);

    $fromC = Carbon::parse($from, $tz)->startOfDay();
    $toC   = Carbon::parse($to,   $tz)->endOfDay();

    // ===== Presensi harian (hadir/telat) — exlude izin/sakit
    $qPres = \App\Models\Presensi::with('user:id,name,role')
        ->whereBetween('tanggal', [$fromC->toDateString(), $toC->toDateString()])
        ->whereNotIn('status', ['izin','sakit'])
        ->orderByDesc('tanggal')
        ->orderByDesc('jam_masuk');

    if ($guruId) $qPres->where('user_id', $guruId);

    $harian = $qPres->get()->map(function($p){
        return (object)[
            'type'        => 'presensi',
            'user_id'     => $p->user_id,
            'user'        => $p->user,          // ->name, ->role
            'status'      => $p->status,        // hadir|telat
            'date_start'  => $p->tanggal,
            'date_end'    => $p->tanggal,
            'jam_masuk'   => $p->jam_masuk,
            'jam_keluar'  => $p->jam_keluar,
            'telat_menit' => $p->telat_menit,
            'id'          => $p->id,
        ];
    });

    // ===== Izin/sakit sebagai RENTANG
    $qIzin = \App\Models\Izin::with('user:id,name,role')
        ->when($guruId, fn($q)=>$q->where('user_id',$guruId))
        ->where(function($q) use ($fromC,$toC){
            // overlap dengan rentang [from..to]
            $fromS = $fromC->toDateString();
            $toS   = $toC->toDateString();
            $q->whereBetween('tgl_mulai',   [$fromS,$toS])
              ->orWhereBetween('tgl_selesai',[$fromS,$toS])
              ->orWhere(function($qq) use($fromS,$toS){
                  $qq->where('tgl_mulai','<=',$fromS)
                     ->where('tgl_selesai','>=',$toS);
              });
        })
        ->orderBy('tgl_mulai','desc');

    $izinRanges = $qIzin->get()->map(function($i) use ($fromC,$toC){
        // potong supaya tetap di bulan/ rentang yg dilihat
        $s = Carbon::parse($i->tgl_mulai)->max($fromC)->toDateString();
        $e = Carbon::parse($i->tgl_selesai)->min($toC)->toDateString();
        return (object)[
            'type'       => 'izin',
            'user_id'    => $i->user_id,
            'user'       => $i->user,
            'status'     => $i->jenis,       // izin | sakit
            'date_start' => $s,
            'date_end'   => $e,
            'approval'   => $i->status,      // approved|pending|rejected
            'keterangan' => $i->keterangan,
            'id'         => $i->id,
        ];
    });

    // ===== Gabungkan & filter status (jika dipilih)
    $timeline = $harian->concat($izinRanges);

    if ($status) {
        $timeline = $timeline->filter(function($row) use ($status){
            if ($row->type === 'presensi') {
                return in_array($status, ['hadir','telat']) ? $row->status === $status : false;
            }
            // type izin
            return in_array($status, ['izin','sakit']) ? $row->status === $status : false;
        });
    }

    // Sort desc by start date, lalu id agar stabil
    $timeline = $timeline->sortBy([
        ['date_start','desc'],
        ['id','desc'],
    ])->values();

    // ===== Pagination untuk Collection
    $perPage = 25;
    $page    = LengthAwarePaginator::resolveCurrentPage() ?: 1;
    $items   = $timeline->slice(($page - 1) * $perPage, $perPage)->values();
    $data    = new LengthAwarePaginator($items, $timeline->count(), $perPage, $page);
    $data->withPath(url()->current())->appends($r->query());

    return view('tu.riwayat', [
        'gurus'   => $gurus,
        'guruId'  => $guruId,
        'from'    => $fromC->toDateString(),
        'to'      => $toC->toDateString(),
        'status'  => $status,
        'data'    => $data,     // <- sudah berisi mix 'presensi' & 'izin' rentang
    ]);
}

    // ====================== (opsional) absen manual milikmu tetap dibiarkan seperti semula ======================
}
