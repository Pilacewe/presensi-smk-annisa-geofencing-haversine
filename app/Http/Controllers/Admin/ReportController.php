<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceSummaryExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(Request $r)
    {
        [$from, $to, $mode, $tahun, $bulan] = $this->resolveRange($r);
        $userId = $r->filled('user_id') ? (int) $r->query('user_id') : null;

        $rows  = $this->summary($from, $to, '', $userId);
        $users = DB::table('users')
            ->whereIn('role', ['guru','tu'])
            ->orderBy('name')
            ->get(['id','name','jabatan']);

        return view('admin.reports.index', compact(
            'rows','from','to','mode','tahun','bulan','users','userId'
        ));
    }

    /** 🔎 PREVIEW HTML sebelum download PDF */
    public function preview(Request $r)
    {
        [$from, $to, $mode, $tahun, $bulan] = $this->resolveRange($r);
        $userId = $r->filled('user_id') ? (int) $r->query('user_id') : null;

        $rows   = $this->summary($from, $to, '', $userId);
        $period = $mode === 'bulan'
            ? Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y')
            : (string) $tahun;

        $user = $userId
            ? DB::table('users')->select('id','name','jabatan')->where('id',$userId)->first()
            : null;

        $downloadUrl = route('admin.reports.export', [
            'mode'    => $mode,
            'tahun'   => $tahun,
            'bulan'   => $bulan,
            'user_id' => $userId,
            'format'  => 'pdf',
        ]);

        return view('admin.reports.preview', compact(
            'rows','from','to','period','downloadUrl','user','mode','tahun','bulan','userId'
        ));
    }

    /** ⬇️ Downloader (xlsx/csv/pdf) */
    public function export(Request $r)
    {
        [$from, $to, $mode, $tahun, $bulan] = $this->resolveRange($r);
        $userId = $r->filled('user_id') ? (int) $r->query('user_id') : null;

        $rows   = $this->summary($from, $to, '', $userId);
        $period = $mode === 'bulan'
            ? Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y')
            : (string) $tahun;

        $format = strtolower($r->query('format', 'xlsx')); // xlsx|csv|pdf
        $suffix = $userId ? ('-uid_'.$userId) : '';
        $fname  = 'laporan-presensi-'.$mode.'-'.$period.$suffix.'.'.$format;

        if (in_array($format, ['xlsx','csv'])) {
            return Excel::download(new AttendanceSummaryExport($rows, $from, $to, $period), $fname);
        }

        // PDF
        $pdf = Pdf::loadView('admin.reports.pdf', [
            'rows'   => $rows,
            'from'   => $from,
            'to'     => $to,
            'period' => $period,
        ])->setPaper('a4','portrait');

        return $pdf->download($fname);
    }

    /* ================= Helpers ================= */

    private function resolveRange(Request $r): array
    {
        $mode  = $r->query('mode', 'bulan'); // bulan|tahun
        $now   = now();
        $tahun = (int) $r->query('tahun', $now->year);
        $bulan = (int) $r->query('bulan', $now->month);

        if ($mode === 'tahun') {
            $from = Carbon::create($tahun, 1, 1)->startOfDay();
            $to   = Carbon::create($tahun, 12, 31)->endOfDay();
        } else {
            $from = Carbon::create($tahun, $bulan, 1)->startOfDay();
            $to   = (clone $from)->endOfMonth()->endOfDay();
        }
        return [$from, $to, $mode, $tahun, $bulan];
    }

    /** Rekap per pegawai (opsional filter 1 pegawai via $userId) */
    private function summary(Carbon $from, Carbon $to, string $q = '', ?int $userId = null)
    {
        $roles = ['guru','tu'];

        return DB::table('users as u')
            ->leftJoin('presensis as p', function($j) use ($from,$to){
                $j->on('p.user_id','=','u.id')
                  ->whereBetween('p.tanggal', [$from->toDateString(), $to->toDateString()]);
            })
            ->whereIn('u.role', $roles)
            ->when($userId, fn($qq)=>$qq->where('u.id',$userId))
            ->when($q !== '', fn($qq)=>$qq->where('u.name','like','%'.$q.'%'))
            ->groupBy('u.id','u.name','u.jabatan')
            ->orderBy('u.name')
            ->get([
                'u.id','u.name','u.jabatan',
                DB::raw("SUM(CASE WHEN p.status='hadir' THEN 1 ELSE 0 END) AS hadir"),
                DB::raw("SUM(CASE WHEN p.status='telat' THEN 1 ELSE 0 END) AS telat"),
                DB::raw("SUM(CASE WHEN p.status='sakit' THEN 1 ELSE 0 END) AS sakit"),
                DB::raw("SUM(CASE WHEN p.status='izin'  THEN 1 ELSE 0 END) AS izin"),
                DB::raw("SUM(CASE WHEN p.status='alpha' THEN 1 ELSE 0 END) AS alpha"),
                DB::raw("AVG(NULLIF(p.telat_menit,0)) AS rata_telat"),
            ])
            ->map(function($r){
                $r->total = $r->hadir + $r->telat + $r->sakit + $r->izin + $r->alpha;
                $r->rata_telat = $r->rata_telat ? round($r->rata_telat,1) : 0;
                return $r;
            });
    }
}
