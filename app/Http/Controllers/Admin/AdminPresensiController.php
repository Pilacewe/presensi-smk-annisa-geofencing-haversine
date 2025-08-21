<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPresensiController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim($request->get('q', ''));
        $role   = $request->get('role');   // admin|guru|tu|piket|kepsek
        $status = $request->get('status'); // hadir|izin|sakit|alfa
        $start  = $request->get('start');  // yyyy-mm-dd
        $end    = $request->get('end');    // yyyy-mm-dd

        $items = Presensi::with(['user:id,name,role'])
            ->when($q, function($qq) use ($q){
                $qq->whereHas('user', fn($u)=>$u->where('name','like',"%$q%")
                                                ->orWhere('email','like',"%$q%"));
            })
            ->when($role, fn($qr)=>$qr->whereHas('user', fn($u)=>$u->where('role',$role)))
            ->when($status, fn($qs)=>$qs->where('status',$status))
            ->when($start, fn($d)=>$d->whereDate('tanggal','>=',$start))
            ->when($end,   fn($d)=>$d->whereDate('tanggal','<=',$end))
            ->orderByDesc('tanggal')->orderByDesc('updated_at')
            ->paginate(15)->withQueryString();

        // Ringkas kecil untuk tampilan
        $ringkas = [
            'total' => (clone $items)->total(),
            'hadir' => Presensi::when($role, fn($qr)=>$qr->whereHas('user', fn($u)=>$u->where('role',$role)))
                               ->when($start, fn($d)=>$d->whereDate('tanggal','>=',$start))
                               ->when($end,   fn($d)=>$d->whereDate('tanggal','<=',$end))
                               ->where('status','hadir')->count(),
            'izin'  => Presensi::when($role, fn($qr)=>$qr->whereHas('user', fn($u)=>$u->where('role',$role)))
                               ->when($start, fn($d)=>$d->whereDate('tanggal','>=',$start))
                               ->when($end,   fn($d)=>$d->whereDate('tanggal','<=',$end))
                               ->where('status','izin')->count(),
            'sakit' => Presensi::when($role, fn($qr)=>$qr->whereHas('user', fn($u)=>$u->where('role',$role)))
                               ->when($start, fn($d)=>$d->whereDate('tanggal','>=',$start))
                               ->when($end,   fn($d)=>$d->whereDate('tanggal','<=',$end))
                               ->where('status','sakit')->count(),
            'alfa'  => Presensi::when($role, fn($qr)=>$qr->whereHas('user', fn($u)=>$u->where('role',$role)))
                               ->when($start, fn($d)=>$d->whereDate('tanggal','>=',$start))
                               ->when($end,   fn($d)=>$d->whereDate('tanggal','<=',$end))
                               ->where('status','alfa')->count(),
        ];

        return view('admin.presensi.index', compact('items','ringkas','q','role','status','start','end'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get(['id','name','role']);
        return view('admin.presensi.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'    => ['required', Rule::exists('users','id')],
            'tanggal'    => ['required','date'],
            'status'     => ['required', Rule::in(['hadir','izin','sakit','alfa'])],
            'jam_masuk'  => ['nullable','date_format:H:i'],
            'jam_keluar' => ['nullable','date_format:H:i','after_or_equal:jam_masuk'],
            'keterangan' => ['nullable','string','max:500'],
        ]);

        // opsional: cegah duplikat per user+tanggal
        $exists = Presensi::where('user_id',$data['user_id'])
                          ->whereDate('tanggal',$data['tanggal'])->exists();
        if ($exists) {
            return back()->withErrors(['tanggal'=>'Sudah ada presensi untuk user & tanggal ini.'])
                         ->withInput();
        }

        Presensi::create($data);
        return redirect()->route('admin.presensi.index')->with('success','Data presensi ditambahkan.');
    }

    public function edit(Presensi $presensi)
    {
        $users = User::orderBy('name')->get(['id','name','role']);
        return view('admin.presensi.edit', compact('presensi','users'));
    }

    public function update(Request $request, Presensi $presensi)
    {
        $data = $request->validate([
            'user_id'    => ['required', Rule::exists('users','id')],
            'tanggal'    => ['required','date'],
            'status'     => ['required', Rule::in(['hadir','izin','sakit','alfa'])],
            'jam_masuk'  => ['nullable','date_format:H:i'],
            'jam_keluar' => ['nullable','date_format:H:i','after_or_equal:jam_masuk'],
            'keterangan' => ['nullable','string','max:500'],
        ]);

        // opsional: cek duplikat saat ganti user/tanggal
        $exists = Presensi::where('user_id',$data['user_id'])
                          ->whereDate('tanggal',$data['tanggal'])
                          ->where('id','!=',$presensi->id)->exists();
        if ($exists) {
            return back()->withErrors(['tanggal'=>'Sudah ada presensi untuk user & tanggal ini.'])
                         ->withInput();
        }

        $presensi->update($data);
        return redirect()->route('admin.presensi.index')->with('success','Data presensi diperbarui.');
    }

    public function destroy(Presensi $presensi)
    {
        $presensi->delete();
        return redirect()->route('admin.presensi.index')->with('success','Data presensi dihapus.');
    }
}
