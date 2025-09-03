<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Izin;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TuExportController extends Controller
{
    public function index(Request $r)
    {
        $tz      = config('app.timezone','Asia/Jakarta');
        $from    = $r->input('from', Carbon::now($tz)->startOfMonth()->toDateString());
        $to      = $r->input('to',   Carbon::now($tz)->endOfMonth()->toDateString());
        $guruId  = $r->input('guru_id');

        $rowsAll = $this->buildDataset($guruId, $from, $to, $tz);

        // paginate collection
        $perPage = 25;
        $page    = LengthAwarePaginator::resolveCurrentPage() ?: 1;
        $items   = $rowsAll->slice(($page-1)*$perPage, $perPage)->values();
        $rows    = new LengthAwarePaginator($items, $rowsAll->count(), $perPage, $page);
        $rows->withPath(url()->current())->appends($r->query());

        $gurus = User::where('role','guru')->orderBy('name')->get(['id','name']);

        return view('tu.export', compact('rows','from','to','guruId','gurus'));
    }

    public function exportExcel(Request $r): StreamedResponse
    {
        $tz     = config('app.timezone','Asia/Jakarta');
        $from   = $r->input('from', Carbon::now($tz)->startOfMonth()->toDateString());
        $to     = $r->input('to',   Carbon::now($tz)->endOfMonth()->toDateString());
        $guruId = $r->input('guru_id');

        $rows = $this->buildDataset($guruId, $from, $to, $tz);

        $filename = 'presensi_' . $from . '_' . $to . '.csv';
        $headers  = ['Content-Type' => 'text/csv; charset=UTF-8'];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel Windows membaca UTF-8
            echo "\xEF\xBB\xBF";
            fputcsv($out, ['Nama', 'Tanggal/Rentang', 'Masuk', 'Keluar', 'Status', 'Keterangan']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->user_name,
                    $r->date_label,
                    $r->jam_masuk ?: '—',
                    $r->jam_keluar ?: '—',
                    strtoupper($r->status_label),
                    $r->keterangan ?: '',
                ]);
            }
            fclose($out);
        }, $filename, $headers);
    }

    public function exportPdf(Request $r)
    {
        $tz     = config('app.timezone','Asia/Jakarta');
        $from   = $r->input('from', Carbon::now($tz)->startOfMonth()->toDateString());
        $to     = $r->input('to',   Carbon::now($tz)->endOfMonth()->toDateString());
        $guruId = $r->input('guru_id');

        $rows = $this->buildDataset($guruId, $from, $to, $tz);

        // Jika Anda sudah pakai dompdf/laravel-snappy, panggil sesuai paketnya.
        // Untuk sederhana, render blade preview PDF:
        return view('tu.export-pdf', compact('rows','from','to'));
    }

    /**
     * Gabungkan PRESENSI harian (hadir/telat) + RENTANG izin/sakit menjadi satu koleksi.
     * Menghasilkan field:
     *  - type: 'presensi'|'izin'
     *  - user_id, user_name
     *  - date_start, date_end, date_label
     *  - jam_masuk, jam_keluar
     *  - status_key (hadir|telat|izin|sakit), status_label
     *  - keterangan (untuk izin/sakit), approval (approved|pending|rejected|null)
     */
    private function buildDataset(?int $guruId, string $from, string $to, string $tz)
    {
        $fromC = Carbon::parse($from, $tz)->startOfDay();
        $toC   = Carbon::parse($to,   $tz)->endOfDay();

        // 1) PRESENSI harian (kecualikan izin/sakit agar tidak dobel)
        $p = Presensi::with('user:id,name')
            ->when($guruId, fn($q)=>$q->where('user_id',$guruId))
            ->whereBetween('tanggal', [$fromC->toDateString(), $toC->toDateString()])
            ->whereNotIn('status', ['izin','sakit'])
            ->orderBy('tanggal','desc')
            ->orderBy('jam_masuk','desc')
            ->get()
            ->map(function($row){
                return (object)[
                    'type'         => 'presensi',
                    'user_id'      => $row->user_id,
                    'user_name'    => $row->user?->name ?? '-',
                    'date_start'   => $row->tanggal,
                    'date_end'     => $row->tanggal,
                    'date_label'   => Carbon::parse($row->tanggal)->translatedFormat('d M Y'),
                    'jam_masuk'    => $row->jam_masuk,
                    'jam_keluar'   => $row->jam_keluar,
                    'status_key'   => $row->status ?: 'hadir',
                    'status_label' => $row->status ?: 'hadir',
                    'keterangan'   => null,
                    'approval'     => null,
                ];
            });

        // 2) IZIN/SAKIT rentang -> SATU baris per izin
        $i = Izin::with('user:id,name')
            ->when($guruId, fn($q)=>$q->where('user_id',$guruId))
            ->where(function($q) use($fromC,$toC){
                $q->whereBetween('tgl_mulai',   [$fromC->toDateString(), $toC->toDateString()])
                  ->orWhereBetween('tgl_selesai',[$fromC->toDateString(), $toC->toDateString()])
                  ->orWhere(function($qq) use($fromC,$toC){
                      $qq->where('tgl_mulai','<=',$fromC->toDateString())
                         ->where('tgl_selesai','>=',$toC->toDateString());
                  });
            })
            ->orderBy('tgl_mulai','desc')
            ->get()
            ->map(function($row) use ($fromC,$toC){
                $start = Carbon::parse($row->tgl_mulai)->max($fromC);
                $end   = Carbon::parse($row->tgl_selesai)->min($toC);

                return (object)[
                    'type'         => 'izin',
                    'user_id'      => $row->user_id,
                    'user_name'    => $row->user?->name ?? '-',
                    'date_start'   => $start->toDateString(),
                    'date_end'     => $end->toDateString(),
                    'date_label'   => $start->equalTo($end)
                        ? $start->translatedFormat('d M Y')
                        : $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
                    'jam_masuk'    => null,
                    'jam_keluar'   => null,
                    'status_key'   => strtolower($row->jenis ?? 'izin'),   // izin/sakit
                    'status_label' => strtoupper($row->jenis ?? 'izin'),
                    'keterangan'   => $row->keterangan,
                    'approval'     => $row->status, // approved|pending|rejected
                ];
            });

        // Gabung + sort
        return $p->concat($i)
                ->sortByDesc(fn($x)=>[$x->date_start, $x->user_name])
                ->values();
    }
}
