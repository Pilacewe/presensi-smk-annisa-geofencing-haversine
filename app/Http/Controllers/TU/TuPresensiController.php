<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\Request;

class TuPresensiController extends Controller
{
    public function index(Request $r)
{
    $tanggal = $r->input('tanggal', now()->toDateString());
    $keyword = $r->input('q');

    // Ambil SEMUA guru + left join ke presensi pada tanggal tsb
    $rows = User::query()
        ->where('role', 'guru')
        ->when($keyword, fn($q) => $q->where('name', 'like', "%{$keyword}%"))
        ->leftJoin('presensis as p', function ($join) use ($tanggal) {
            $join->on('p.user_id', '=', 'users.id')
                 ->whereDate('p.tanggal', $tanggal);
        })
        ->orderBy('users.name')
        ->select([
            'users.id',
            'users.name',
            'users.jabatan',
            'p.id as presensi_id',
            'p.status',
            'p.jam_masuk',
            'p.jam_keluar',
        ])
        ->paginate(20)
        ->withQueryString();

    // Ringkasan status hari itu (hanya yang terisi baris)
    $summary = Presensi::whereDate('tanggal', $tanggal)->get()->groupBy('status');
    $stat = [
        'hadir' => $summary->get('hadir')?->count() ?? 0,
        'izin'  => $summary->get('izin')?->count()  ?? 0,
        'sakit' => $summary->get('sakit')?->count() ?? 0,
        // belum absen = total guru - (hadir+izin+sakit)
        'belum' => (int) User::where('role','guru')->count()
                    - (($summary->get('hadir')?->count() ?? 0)
                    +  ($summary->get('izin')?->count()  ?? 0)
                    +  ($summary->get('sakit')?->count() ?? 0)),
    ];

    return view('tu.presensi-index', compact('rows', 'tanggal', 'keyword', 'stat'));
}

    public function riwayat(Request $r)
    {
        $guruId = $r->input('guru_id');
        $from   = $r->input('from', now()->startOfMonth()->toDateString());
        $to     = $r->input('to',   now()->endOfMonth()->toDateString());
        $status = $r->input('status');

        $gurus = User::where('role','guru')->orderBy('name')->get(['id','name']);

        $q = Presensi::with('user:id,name')
            ->whereBetween('tanggal',[$from,$to])
            ->orderByDesc('tanggal')->orderByDesc('jam_masuk');

        if ($guruId) $q->where('user_id',$guruId);
        if ($status) $q->where('status',$status);

        $data = $q->paginate(25)->withQueryString();
        return view('tu.riwayat', compact('gurus','guruId','from','to','status','data'));
    }

    public function create()
    {
        $gurus = User::where('role','guru')->orderBy('name')->get(['id','name']);
        return view('tu.absen', compact('gurus'));
    }

    public function store(Request $r)
    {
        $r->validate([
            'user_id'=>'required|exists:users,id',
            'mode'=>'required|in:masuk,keluar',
            'tanggal'=>'required|date',
            'latitude'=>'required|numeric',
            'longitude'=>'required|numeric',
        ]);

        $latUser=(float)$r->latitude; $lngUser=(float)$r->longitude;
        $latBase=(float)config('presensi.lat'); $lngBase=(float)config('presensi.lng');
        $radius=(float)config('presensi.radius');

        $jarak=$this->distanceMeters($latUser,$lngUser,$latBase,$lngBase);
        if ($jarak>$radius) return back()->with('warning','Di luar area (± '.number_format($jarak,0).' m).');

        $presensi = Presensi::firstOrCreate(
            ['user_id'=>$r->user_id,'tanggal'=>$r->tanggal],
            ['status'=>'hadir']
        );

        if ($r->mode==='masuk') {
            if ($presensi->jam_masuk) return back()->with('message','Sudah ada jam masuk.');
            $presensi->update([
                'jam_masuk'=>$r->jam ?: now()->format('H:i:s'),
                'latitude'=>$latUser,'longitude'=>$lngUser,'status'=>'hadir',
            ]);
            return back()->with('success','Presensi MASUK disimpan.');
        }

        if ($presensi->jam_keluar) return back()->with('message','Sudah ada jam keluar.');
        if (!$presensi->jam_masuk) return back()->with('message','Belum ada jam masuk.');
        $presensi->update([
            'jam_keluar'=>$r->jam ?: now()->format('H:i:s'),
            'latitude'=>$latUser,'longitude'=>$lngUser,
        ]);
        return back()->with('success','Presensi KELUAR disimpan.');
    }

    private function distanceMeters($lat1,$lng1,$lat2,$lng2): float
    {
        $R=6371000; $dLat=deg2rad($lat2-$lat1); $dLng=deg2rad($lng2-$lng1);
        $a=sin($dLat/2)**2+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
        return 2*$R*atan2(sqrt($a),sqrt(1-$a));
    }
}
