<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;

class PiketDashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $totalGuru = User::where('role','guru')->count();
        $hadir = Presensi::whereDate('tanggal',$today)->where('status','hadir')->count();
        $izin  = Presensi::whereDate('tanggal',$today)->where('status','izin')->count();
        $sakit = Presensi::whereDate('tanggal',$today)->where('status','sakit')->count();

        // log terbaru hari ini
        $recent = Presensi::with('user:id,name')
            ->whereDate('tanggal',$today)
            ->orderByDesc('jam_masuk')
            ->orderByDesc('jam_keluar')
            ->take(10)
            ->get();

        return view('piket.dashboard', compact('totalGuru','hadir','izin','sakit','recent'));
    }
}
