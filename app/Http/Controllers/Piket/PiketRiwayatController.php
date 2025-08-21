<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PiketRiwayatController extends Controller
{
    public function index(Request $request)
    {
        $tz = config('app.timezone', 'Asia/Jakarta');

        // Filter inputs (dengan default yang nyaman)
        $guruId = $request->input('guru_id'); // samakan dengan name di view
        $start  = $request->input('start', Carbon::now($tz)->startOfMonth()->toDateString());
        $end    = $request->input('end',   Carbon::now($tz)->toDateString());

        // List guru untuk dropdown
        $guruList = User::where('role', 'guru')
            ->orderBy('name')
            ->get(['id','name']);

        $q = Presensi::with('user')
            ->whereHas('user', fn($u) => $u->where('role','guru'))
            ->orderByDesc('tanggal');

        // Terapkan filter
        if (!empty($guruId)) {
            $q->where('user_id', $guruId);
        }

        if ($start && $end) {
            $q->whereBetween('tanggal', [$start, $end]);
        } elseif ($start) {
            $q->whereDate('tanggal', '>=', $start);
        } elseif ($end) {
            $q->whereDate('tanggal', '<=', $end);
        }

        $data = $q->paginate(15)->withQueryString();

        // kirim ke view piket/riwayat/index.blade.php
        return view('piket.riwayat', [
            'data'     => $data,
            'guruList' => $guruList,
            'guruId'   => $guruId,
            'start'    => $start,
            'end'      => $end,
        ]);
    }
}
