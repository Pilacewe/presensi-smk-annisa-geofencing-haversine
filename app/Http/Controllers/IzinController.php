<?php

namespace App\Http\Controllers;

use App\Models\Izin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class IzinController extends Controller
{
    public function index(Request $r)
    {
        $q = Izin::where('user_id', Auth::id())->latest();

        if ($jenis = $r->input('jenis'))   $q->where('jenis', $jenis);
        if ($status = $r->input('status')) $q->where('status', $status);

        $data = $q->paginate(10)->withQueryString();

        return view('izin.index', compact('data'));
    }

    public function create()
    {
        return view('izin.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'jenis'       => 'required|in:izin,sakit',     // jika mau 'dinas', tambahkan di sini
        'tgl_mulai'   => 'required|date',
        'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
        'keterangan'  => 'nullable|string|max:500',
        'bukti'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $path = null;
    if ($request->hasFile('bukti')) {
        $path = $request->file('bukti')->store('izin_bukti', 'public');
    }

    Izin::create([
        'user_id'     => auth()->id(),
        'jenis'       => $request->jenis,
        'tgl_mulai'   => $request->tgl_mulai,
        'tgl_selesai' => $request->tgl_selesai,
        'keterangan'  => $request->keterangan,
        'status'      => 'pending',
        'bukti'       => $path,
    ]);

    return redirect()->route('izin.index')->with('success','Pengajuan izin dikirim.');
}

    public function show(Izin $izin)
    {
        $this->authorizeView($izin);
        return view('izin.show', compact('izin'));
    }

    public function destroy(Izin $izin)
{
    $this->authorizeView($izin);

    if ($izin->status !== 'pending') {
        return back()->with('message','Pengajuan yang sudah diproses tidak dapat dibatalkan.');
    }

    if ($izin->bukti) {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($izin->bukti);
    }

    $izin->delete();

    return redirect()->route('izin.index')->with('success','Pengajuan izin dibatalkan.');
}


    private function authorizeView(Izin $izin)
    {
        abort_unless($izin->user_id === Auth::id(), 403);
    }
}
