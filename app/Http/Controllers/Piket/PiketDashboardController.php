<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;

class PiketDashboardController extends Controller
{
    public function index()
    {
        // Gunakan timezone dari config
        $tz = config('app.timezone', 'Asia/Jakarta');
        $today = Carbon::now($tz)->toDateString();

        // Ambil semua guru
        $guru = User::where('role', 'guru')
            ->orderBy('name')
            ->get(['id','name']);

        // Ambil presensi hari ini (di-key-kan berdasarkan user_id)
        $map = Presensi::with('user')
            ->whereDate('tanggal', $today)
            ->get()
            ->keyBy('user_id');

        // Susun status tiap guru (hadir/izin/sakit/belum)
        $rows = $guru->map(function ($g) use ($map) {
            $p = $map->get($g->id);
            return (object) [
                'user'       => $g,
                'status'     => $p ? ($p->status ?? 'hadir') : 'belum',
                'jam_masuk'  => $p->jam_masuk ?? null,
                'jam_keluar' => $p->jam_keluar ?? null,
            ];
        });

        // Hitung ringkasan
        $totalGuru = $guru->count();
        $hadir     = $rows->where('status','hadir')->count();
        $izin      = $rows->where('status','izin')->count();
        $sakit     = $rows->where('status','sakit')->count();
        $belum     = $rows->where('status','belum')->count();

        // Ambil aktivitas terbaru (10 terakhir)
        $recent = Presensi::with('user')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        // Kirim data ke view
        return view('piket.dashboard', [
            'totalGuru' => $totalGuru,
            'hadir'     => $hadir,
            'izin'      => $izin,
            'sakit'     => $sakit,
            'belum'     => $belum,
            'recent'    => $recent,
            'todayRows' => $rows,
        ]);
    }
}
