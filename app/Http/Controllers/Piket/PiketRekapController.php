<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PiketRekapController extends Controller
{
    public function index(Request $request)
    {
        $tz       = config('app.timezone', 'Asia/Jakarta');
        $tanggal  = $request->input('tanggal', Carbon::now($tz)->toDateString());
        $filterSt = $request->input('status'); // null|hadir|izin|sakit|belum

        // Ambil semua guru (id, name)
        $guruList = User::where('role', 'guru')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Ambil presensi pada tanggal tsb, keyBy user_id agar lookup cepat
        $presensiHariIni = Presensi::whereDate('tanggal', $tanggal)
            ->get(['user_id', 'status', 'jam_masuk', 'jam_keluar'])
            ->keyBy('user_id');

        // Helper format jam -> "HH:MM"
        $fmtJam = function ($val) use ($tz) {
            if (!$val) return null;

            try {
                // "08:15:00"
                if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $val)) {
                    return Carbon::createFromFormat('H:i:s', $val)->format('H:i');
                }
                // "08:15"
                if (preg_match('/^\d{2}:\d{2}$/', $val)) {
                    return $val;
                }
                // Timestamp / string lain yang valid
                return Carbon::parse($val, $tz)->format('H:i');
            } catch (\Throwable $e) {
                // Fallback aman
                return substr((string) $val, 0, 5);
            }
        };

        // Susun rows lengkap semua guru (termasuk yang belum presensi)
        $rows = $guruList->map(function ($g) use ($presensiHariIni, $fmtJam) {
            $p = $presensiHariIni->get($g->id);

            $status = $p
                ? ($p->status ?: 'hadir') // jika ada record tapi status null -> hadirin
                : 'belum';                // tidak ada record -> belum absen

            return (object) [
                'user'       => $g,
                'status'     => $status,
                'jam_masuk'  => $fmtJam($p->jam_masuk ?? null),
                'jam_keluar' => $fmtJam($p->jam_keluar ?? null),
            ];
        });

        // Ringkasan
        $totalGuru = $guruList->count();
        $hadir     = $rows->where('status', 'hadir')->count();
        $izin      = $rows->where('status', 'izin')->count();
        $sakit     = $rows->where('status', 'sakit')->count();
        $belum     = $rows->where('status', 'belum')->count();

        // Filter status (opsional)
        if ($filterSt) {
            $rows = $rows->where('status', $filterSt)->values();
        }

        return view('piket.rekap', [
            'tanggal'   => $tanggal,
            'totalGuru' => $totalGuru,
            'hadir'     => $hadir,
            'izin'      => $izin,
            'sakit'     => $sakit,
            'belum'     => $belum,
            'rows'      => $rows,
            'filterSt'  => $filterSt,
        ]);
    }
}
