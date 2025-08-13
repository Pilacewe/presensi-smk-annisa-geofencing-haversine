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
        $validated = $request->validate([
            'jenis'       => ['required', Rule::in(['izin','sakit','dinas'])],
            'tgl_mulai'   => ['required','date'],
            'tgl_selesai' => ['required','date','after_or_equal:tgl_mulai'],
            'keterangan'  => ['nullable','string','max:2000'],
            'lampiran'    => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:2048'],
        ],[
            'lampiran.mimes' => 'Lampiran harus jpg/jpeg/png/pdf',
            'lampiran.max'   => 'Ukuran maksimal 2MB',
        ]);

        // upload lampiran (opsional)
        $path = null;
        if ($request->hasFile('lampiran')) {
            $path = $request->file('lampiran')->store('izin', 'public'); // storage/app/public/izin
        }

        $izin = Izin::create([
            'user_id'      => Auth::id(),
            'jenis'        => $validated['jenis'],
            'tgl_mulai'    => $validated['tgl_mulai'],
            'tgl_selesai'  => $validated['tgl_selesai'],
            'keterangan'   => $validated['keterangan'] ?? null,
            'lampiran_path'=> $path,
            'status'       => 'pending',
        ]);

        return redirect()->route('izin.show',$izin)->with('success','Pengajuan izin dikirim & menunggu persetujuan.');
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
        if ($izin->lampiran_path) Storage::disk('public')->delete($izin->lampiran_path);
        $izin->delete();
        return redirect()->route('izin.index')->with('success','Pengajuan izin dibatalkan.');
    }

    private function authorizeView(Izin $izin)
    {
        abort_unless($izin->user_id === Auth::id(), 403);
    }
}
