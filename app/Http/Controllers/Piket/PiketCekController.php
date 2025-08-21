<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PiketCekController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        // List semua guru + presensi hari ini (bisa null = belum absen)
        $items = User::where('role','guru')
            ->with(['presensis' => function($q) use ($today){
                $q->whereDate('tanggal',$today);
            }])
            ->orderBy('name')
            ->paginate(15);

        return view('piket.cek', compact('items','today'));
    }

    // Tampilkan form absen manual (piket)
    public function create()
    {
        // daftar guru saja
        $gurus = User::where('role', 'guru')->orderBy('name')->get();

        return view('piket.absen.create', compact('gurus'));
    }

    // Simpan absen manual
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'tipe'    => 'required|in:masuk,keluar',
            'jam'     => 'required|date_format:H:i',
        ]);

        $userId  = (int) $request->user_id;
        $tanggal = Carbon::parse($request->tanggal)->toDateString();
        $jam     = $request->jam;

        $rec = Presensi::firstOrCreate(
            ['user_id' => $userId, 'tanggal' => $tanggal],
            ['status'  => 'hadir']
        );

        if ($request->tipe === 'masuk') {
            // cegah overwrite
            if ($rec->jam_masuk) {
                return back()->with('message', 'Guru sudah tercatat MASUK.')->withInput();
            }
            $rec->jam_masuk = $jam . ':00';
        } else {
            if (! $rec->jam_masuk) {
                return back()->with('message', 'Belum ada presensi MASUK untuk tanggal ini.')->withInput();
            }
            if ($rec->jam_keluar) {
                return back()->with('message', 'Guru sudah tercatat KELUAR.')->withInput();
            }
            $rec->jam_keluar = $jam . ':00';
        }

        $rec->save();

        return redirect()
            ->route('piket.absen.create')
            ->with('success', 'Absen manual disimpan.');
    }
    
    public function riwayat(Request $request)
{
    $q = \App\Models\Presensi::with('user')
        ->whereHas('user', fn($u)=>$u->where('role','guru'))
        ->orderByDesc('tanggal');

    // filter opsional
    if ($request->filled('guru')) {
        $q->where('user_id', $request->guru);
    }
    if ($request->filled('bulan')) {
        $q->whereMonth('tanggal', $request->bulan);
    }
    if ($request->filled('tahun')) {
        $q->whereYear('tanggal', $request->tahun);
    }

    $data = $q->paginate(15)->withQueryString();

    return view('piket.riwayat.index', compact('data'));
}

}
