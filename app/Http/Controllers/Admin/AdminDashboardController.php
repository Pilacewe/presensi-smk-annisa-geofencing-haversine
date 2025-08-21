<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Presensi;
use App\Models\Izin;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $tz     = config('app.timezone', 'Asia/Jakarta');
        $today  = Carbon::now($tz)->toDateString();

        // Count user by role
        $totalUser = User::count();
        $count = [
            'admin' => User::where('role','admin')->count(),
            'guru'  => User::where('role','guru')->count(),
            'tu'    => User::where('role','tu')->count(),
            'piket' => User::where('role','piket')->count(),
            'kepsek'=> User::where('role','kepsek')->count(),
        ];

        // Presensi hari ini (semua pegawai)
        $todayPresensi = Presensi::with('user')->whereDate('tanggal', $today)->get();

        $hadir = $todayPresensi->where('status','hadir')->count();
        $izin  = $todayPresensi->where('status','izin')->count();
        $sakit = $todayPresensi->where('status','sakit')->count();

        // Estimasi “belum absen” = semua pegawai aktif (kecuali admin?) - presensi tercatat.
        // Di sini contoh: semua user non-admin dihitung sebagai pegawai.
        $pegawaiCount = User::whereIn('role', ['guru','tu','piket','kepsek'])->count();
        $sudahAbsen   = $todayPresensi->count(); // ada baris presensi (status apapun)
        $belum        = max($pegawaiCount - $sudahAbsen, 0);

        // Log aktivitas terbaru (lintas hari)
        $recent = Presensi::with('user')->orderByDesc('updated_at')->limit(12)->get();

        // Izin pending (butuh approval admin) — lintas role
        $izinPending = Izin::with('user')->where('status','pending')
            ->orderByDesc('created_at')->limit(8)->get();

        return view('admin.dashboard', [
            'totalUser' => $totalUser,
            'count'     => $count,
            'pegawai'   => $pegawaiCount,
            'hadir'     => $hadir,
            'izin'      => $izin,
            'sakit'     => $sakit,
            'belum'     => $belum,
            'recent'    => $recent,
            'izinPending'=> $izinPending,
        ]);
    }
}
