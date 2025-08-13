<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TuDashboardController extends Controller
{
    public function index()
    {
        // ====== Statistik untuk kartu-kartu TU (guru) ======
        $today     = now()->toDateString();
        $totalGuru = User::where('role', 'guru')->count();

        $hadir = Presensi::whereDate('tanggal', $today)
                 ->where('status','hadir')->count();
        $izin  = Presensi::whereDate('tanggal', $today)
                 ->where('status','izin')->count();
        $sakit = Presensi::whereDate('tanggal', $today)
                 ->where('status','sakit')->count();

        $recent = Presensi::with('user:id,name')
                    ->orderByDesc('tanggal')
                    ->orderByDesc('jam_masuk')
                    ->take(10)->get();

        // ====== Presensi Saya (TU) ======
        $me          = Auth::user();
        $todayRecord = Presensi::where('user_id', $me->id)
                          ->where('tanggal', $today)
                          ->first();

        // Window waktu dari config
        $now      = now()->format('H:i');
        $mStart   = config('presensi.jam_masuk_start',  '06:30');
        $mEnd     = config('presensi.jam_masuk_end',    '09:00');
        $kStart   = config('presensi.jam_keluar_start', '15:30');
        $kEnd     = config('presensi.jam_keluar_end',   '18:00');

        $canMasuk  = empty($todayRecord?->jam_masuk)  && ($now >= $mStart && $now <= $mEnd);
        $canKeluar = !empty($todayRecord?->jam_masuk) && empty($todayRecord?->jam_keluar)
                     && ($now >= $kStart && $now <= $kEnd);

        return view('tu.dashboard', compact(
            'totalGuru','hadir','izin','sakit','recent',
            'todayRecord','canMasuk','canKeluar','mStart','mEnd','kStart','kEnd'
        ));
    }
}
