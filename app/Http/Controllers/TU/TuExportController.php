<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\Request;

class TuExportController extends Controller
{
    public function index(Request $r)
    {
        $gurus = User::where('role','guru')->orderBy('name')->get(['id','name']);
        $from = $r->input('from', now()->startOfMonth()->toDateString());
        $to   = $r->input('to',   now()->endOfMonth()->toDateString());
        $guruId = $r->input('guru_id');
        return view('tu.export', compact('gurus','from','to','guruId'));
    }

    public function exportExcel(Request $r)
    {
        $r->validate(['from'=>'required|date','to'=>'required|date','guru_id'=>'nullable|exists:users,id']);

        $rows = Presensi::with('user:id,name')
            ->whereBetween('tanggal',[$r->from,$r->to])
            ->when($r->guru_id, fn($q)=>$q->where('user_id',$r->guru_id))
            ->orderBy('tanggal')->get();

        $csv = "Nama,Tanggal,Masuk,Keluar,Status\n";
        foreach ($rows as $row) {
            $csv .= sprintf("\"%s\",%s,%s,%s,%s\n",
                $row->user->name, $row->tanggal, $row->jam_masuk ?: '-', $row->jam_keluar ?: '-', $row->status);
        }

        return response($csv)
            ->header('Content-Type','text/csv')
            ->header('Content-Disposition','attachment; filename="presensi_'.now()->format('Ymd_His').'.csv"');
    }

    public function exportPdf(Request $r)
    {
        $r->validate(['from'=>'required|date','to'=>'required|date','guru_id'=>'nullable|exists:users,id']);

        $rows = Presensi::with('user:id,name')
            ->whereBetween('tanggal',[$r->from,$r->to])
            ->when($r->guru_id, fn($q)=>$q->where('user_id',$r->guru_id))
            ->orderBy('tanggal')->get();

        return view('tu.export-pdf', ['rows'=>$rows,'from'=>$r->from,'to'=>$r->to]);
    }
}
