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

        // Kartu statistik (hari ini)
        $totalGuru = User::where('role','guru')->count();
        $hadir = Presensi::whereDate('tanggal', $today)->where('status','hadir')->count();
        $izin  = Presensi::whereDate('tanggal', $today)->where('status','izin')->count();
        $sakit = Presensi::whereDate('tanggal', $today)->where('status','sakit')->count();

        // ====== LOG AKTIVITAS ======
        // Opsi A: HANYA HARI INI
        // $recent = Presensi::with('user:id,name')
        //     ->whereDate('tanggal',$today)
        //     ->orderByDesc('tanggal')
        //     ->orderByDesc('jam_masuk')
        //     ->take(20)
        //     ->get();

        // Opsi B: TERAKHIR (lintas hari) — DISARANKAN AGAR SELALU ADA DATA
        $recent = Presensi::with('user:id,name')
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_masuk')
            ->take(20)
            ->get();

        return view('piket.dashboard', compact('totalGuru','hadir','izin','sakit','recent','today'));
    }
}
