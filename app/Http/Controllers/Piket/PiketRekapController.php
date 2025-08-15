<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\Request;

class PiketRekapController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());

        $rows = Presensi::with('user:id,name')
            ->whereDate('tanggal',$tanggal)
            ->orderBy('jam_masuk')
            ->get();

        $totalGuru = User::where('role','guru')->count();
        $hadir = $rows->where('status','hadir')->count();
        $izin  = $rows->where('status','izin')->count();
        $sakit = $rows->where('status','sakit')->count();
        $belum = $totalGuru - ($hadir + $izin + $sakit);

        return view('piket.rekap', compact('tanggal','rows','totalGuru','hadir','izin','sakit','belum'));
    }
}
