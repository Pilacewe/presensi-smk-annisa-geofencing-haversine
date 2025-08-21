<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PresensiController extends Controller
{
    public function index(Request $request)
    {
        $role    = $request->input('role');             // guru|piket|tu|kepsek|admin|...
        $user_id = $request->input('user_id');          // filter pegawai
        $status  = $request->input('status');           // hadir|izin|sakit|alfa
        $start   = $request->input('start');            // yyyy-mm-dd
        $end     = $request->input('end');              // yyyy-mm-dd

        $q = Presensi::with('user')->orderByDesc('tanggal')->orderByDesc('updated_at');

        // Filter join user/role
        if ($role) {
            $q->whereHas('user', fn($u)=> $u->where('role', $role));
        }

        if ($user_id) $q->where('user_id', $user_id);
        if ($status)  $q->where('status', $status);

        if ($start)   $q->whereDate('tanggal', '>=', $start);
        if ($end)     $q->whereDate('tanggal', '<=', $end);

        $data = $q->paginate(15)->withQueryString();

        // Dropdown user & role
        $users = User::orderBy('name')->get(['id','name','role']);
        $roles = User::select('role')->distinct()->pluck('role')->filter()->values();

        // ringkas kecil
        $summary = [
            'hadir' => (clone $q)->where('status','hadir')->count(),
            'izin'  => (clone $q)->where('status','izin')->count(),
            'sakit' => (clone $q)->where('status','sakit')->count(),
            'alfa'  => (clone $q)->where('status','alfa')->count(),
        ];

        // default tanggal
        $tz   = config('app.timezone','Asia/Jakarta');
        $defS = Carbon::now($tz)->startOfMonth()->toDateString();
        $defE = Carbon::now($tz)->toDateString();

        return view('admin.presensi.index', compact(
            'data','users','roles','summary','role','user_id','status','start','end','defS','defE'
        ));
    }

    public function edit(Presensi $presensi)
    {
        $presensi->load('user');
        return view('admin.presensi.edit', compact('presensi'));
    }

    public function update(Request $request, Presensi $presensi)
    {
        $request->validate([
            'tanggal'    => ['required','date'],
            'status'     => ['required', Rule::in(['hadir','izin','sakit','alfa'])],
            'jam_masuk'  => ['nullable','date_format:H:i'],
            'jam_keluar' => ['nullable','date_format:H:i','after_or_equal:jam_masuk'],
        ],[
            'jam_keluar.after_or_equal' => 'Jam keluar harus >= jam masuk.'
        ]);

        $presensi->update([
            'tanggal'    => $request->tanggal,
            'status'     => $request->status,
            'jam_masuk'  => $request->jam_masuk ? $request->jam_masuk.':00' : null,
            'jam_keluar' => $request->jam_keluar ? $request->jam_keluar.':00' : null,
        ]);

        return redirect()->route('admin.presensi.index')
            ->with('success','Presensi berhasil diperbarui.');
    }
}
