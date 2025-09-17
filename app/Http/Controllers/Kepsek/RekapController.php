<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RekapController extends Controller
{
    /**
     * Rekap Harian: daftar presensi pada tanggal tertentu.
     * (Opsional) ikutkan daftar guru yang belum presensi hari itu.
     */
    public function harian(Request $request)
    {
        $tanggal = $request->input('date', Carbon::today()->toDateString());

        // Presensi yang tercatat pada tanggal tsb
        $rows = DB::table('presensis as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->select(
                'u.name',
                'u.jabatan',
                'u.email',
                'p.status',
                'p.jam_masuk',
                'p.jam_keluar',
                // Hapus kolom ini jika memang tidak ada pada tabel Anda
                'p.telat_menit'
            )
            ->whereDate('p.tanggal', $tanggal)
            ->orderBy('u.name')
            ->get();

        // (Opsional) daftar guru yang belum presensi (tidak wajib dipakai di blade)
        $sudahIds = DB::table('presensis')
            ->whereDate('tanggal', $tanggal)
            ->where(function ($q) {
                $q->whereIn('status', ['hadir', 'telat'])
                  ->orWhereNotNull('jam_masuk');
            })
            ->distinct()
            ->pluck('user_id');

        $belumAbsen = DB::table('users')
            ->where('role', 'guru')               // sesuaikan jika mau termasuk TU
            ->whereNotIn('id', $sudahIds)
            ->orderBy('name')
            ->get(['name','jabatan','id']);

        return view('kepsek.rekap-harian', compact('tanggal', 'rows', 'belumAbsen'));
    }

    /**
     * Rekap Bulanan: pastikan SEMUA guru muncul,
     * meskipun tidak punya baris presensi (nilai agregat 0).
     */
    public function bulanan(Request $request)
    {
        $now   = now();
        $bulan = (int) $request->input('bulan', $now->month);
        $tahun = (int) $request->input('tahun', $now->year);

        $start = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        // KUNCI: pakai leftJoin dan taruh filter tanggal di closure join
        $rows = DB::table('users as u')
            ->leftJoin('presensis as p', function ($j) use ($start, $end) {
                $j->on('p.user_id', '=', 'u.id')
                  ->whereBetween('p.tanggal', [
                      $start->toDateString(),
                      $end->toDateString()
                  ]);
            })
            ->where('u.role', 'guru') // atau ->whereIn('u.role', ['guru','tu'])
            ->groupBy('u.id', 'u.name', 'u.jabatan')
            ->orderBy('u.name')
            ->get([
                'u.id', 'u.name', 'u.jabatan',
                DB::raw("SUM(CASE WHEN p.status='hadir' THEN 1 ELSE 0 END)  AS hadir"),
                DB::raw("SUM(CASE WHEN p.status='telat' THEN 1 ELSE 0 END)  AS telat"),
                DB::raw("SUM(CASE WHEN p.status='sakit' THEN 1 ELSE 0 END)  AS sakit"),
                DB::raw("SUM(CASE WHEN p.status='izin'  THEN 1 ELSE 0 END)  AS izin"),
                DB::raw("SUM(CASE WHEN p.status='alpha' THEN 1 ELSE 0 END)  AS alpha"),
            ]);

        return view('kepsek.rekap-bulanan', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'rows'  => $rows,
            'start' => $start,
            'end'   => $end,
        ]);
    }
}
