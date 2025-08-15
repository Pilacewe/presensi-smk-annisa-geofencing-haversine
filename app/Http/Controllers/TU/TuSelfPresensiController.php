<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Izin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TuSelfPresensiController extends Controller
{
    private function nowWIB(): Carbon
    {
        return Carbon::now(config('app.timezone', 'Asia/Jakarta'));
    }

    private function base(): array
    {
        return [
            'lat'    => (float) config('presensi.lat'),
            'lng'    => (float) config('presensi.lng'),
            'radius' => (float) config('presensi.radius'),
        ];
    }

    public function index(Request $request)
    {
        $tab   = $request->input('tab', 'absen'); // absen|riwayat|izin
        $user  = Auth::user();
        $now   = $this->nowWIB();

        $deadlineMasuk = config('presensi.jam_masuk_end', '08:00');
        $mulaiKeluar   = config('presensi.jam_keluar_start', '16:00');

        // Log presensi hari ini (untuk disable tombol)
        $todayRec = Presensi::where('user_id',$user->id)
            ->where('tanggal',$now->toDateString())
            ->first();

        $base = $this->base();

        // Data riwayat jika tab=riwayat
        $data = collect();
        if ($tab === 'riwayat') {
            $tahun = $request->input('tahun', $now->year);
            $bulan = $request->input('bulan', $now->month);
            $status= $request->input('status'); // null|hadir|izin|sakit|alfa

            $q = Presensi::where('user_id',$user->id)
                    ->whereYear('tanggal',$tahun)
                    ->whereMonth('tanggal',$bulan)
                    ->orderByDesc('tanggal');
            if ($status) $q->where('status',$status);
            $data = $q->paginate(12)->withQueryString();
        }

        // Data izin jika tab=izin
        $izinItems = collect();
        if ($tab === 'izin') {
            $izinItems = Izin::where('user_id',$user->id)
                ->orderByDesc('created_at')
                ->paginate(10)
                ->withQueryString();
        }

        return view('tu.absensi.index', compact(
            'tab','now','base','todayRec','deadlineMasuk','mulaiKeluar','data','izinItems'
        ));
    }

    public function formMasuk()
    {
        $now          = $this->nowWIB();
        $base         = $this->base();
        $startStr     = config('presensi.jam_masuk_start','07:00');
        $endStr       = config('presensi.jam_masuk_end','08:00');

        $allowMasuk   = $now->between(
            $now->copy()->setTimeFromTimeString($startStr),
            $now->copy()->setTimeFromTimeString($endStr)
        );

        return view('tu.absensi.form', [
            'mode'         => 'masuk',
            'now'          => $now,
            'base'         => $base,
            'deadlineMasuk'=> $endStr,
            'mulaiKeluar'  => null,
            'allowMasuk'   => $allowMasuk,
            'allowKeluar'  => null,
        ]);
    }

    public function storeMasuk(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $now      = $this->nowWIB();
        $startStr = config('presensi.jam_masuk_start','07:00');
        $endStr   = config('presensi.jam_masuk_end','08:00');
        $start    = $now->copy()->setTimeFromTimeString($startStr);
        $end      = $now->copy()->setTimeFromTimeString($endStr);

        // batas waktu
        if (!$now->between($start, $end)) {
            return back()->with('message', "Presensi masuk hanya {$startStr}-{$endStr}")
                         ->withInput();
        }

        // geofence
        $jarak = $this->jarak(
            (float)$request->latitude, (float)$request->longitude,
            (float)config('presensi.lat'), (float)config('presensi.lng')
        );
        if ($jarak > (float)config('presensi.radius')) {
            return back()->with('message', 'Di luar area presensi (± '.number_format($jarak,0).' m).');
        }

        $user  = Auth::user();
        $today = $now->toDateString();

        $rec = Presensi::firstOrCreate(
            ['user_id'=>$user->id,'tanggal'=>$today],
            ['status'=>'hadir']
        );
        if ($rec->jam_masuk) {
            return back()->with('message','Presensi MASUK sudah tercatat.');
        }

        $rec->update([
            'jam_masuk' => $now->format('H:i:s'),
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
            'status'    => 'hadir',
        ]);

        return redirect()->route('tu.absensi.index')->with('success','Presensi MASUK berhasil!');
    }

    public function formKeluar()
    {
        $now         = $this->nowWIB();
        $base        = $this->base();
        $startKeluar = config('presensi.jam_keluar_start','16:00');

        $allowKeluar = $now->greaterThanOrEqualTo(
            $now->copy()->setTimeFromTimeString($startKeluar)
        );

        return view('tu.absensi.form', [
            'mode'         => 'keluar',
            'now'          => $now,
            'base'         => $base,
            'deadlineMasuk'=> null,
            'mulaiKeluar'  => $startKeluar,
            'allowMasuk'   => null,
            'allowKeluar'  => $allowKeluar,
        ]);
    }

    public function storeKeluar(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $now         = $this->nowWIB();
        $startKeluar = config('presensi.jam_keluar_start','16:00');

        if ($now->lt($now->copy()->setTimeFromTimeString($startKeluar))) {
            return back()->with('message', "Presensi keluar dibuka {$startKeluar}")
                         ->withInput();
        }

        // geofence
        $jarak = $this->jarak(
            (float)$request->latitude, (float)$request->longitude,
            (float)config('presensi.lat'), (float)config('presensi.lng')
        );
        if ($jarak > (float)config('presensi.radius')) {
            return back()->with('message', 'Di luar area presensi (± '.number_format($jarak,0).' m).');
        }

        $user  = Auth::user();
        $today = $now->toDateString();

        $rec = Presensi::where('user_id',$user->id)->where('tanggal',$today)->first();
        if (!$rec || !$rec->jam_masuk) {
            return back()->with('message','Silakan presensi MASUK terlebih dahulu.');
        }
        if ($rec->jam_keluar) {
            return back()->with('message','Presensi KELUAR sudah tercatat.');
        }

        $rec->update([
            'jam_keluar' => $now->format('H:i:s'),
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
        ]);

        return redirect()->route('tu.absensi.index')->with('success','Presensi KELUAR berhasil!');
    }

    // -------- Izin TU pribadi (sederhana) ----------
    public function izinIndex()
    {
        $items = Izin::where('user_id',Auth::id())->orderByDesc('created_at')->paginate(10);
        return view('tu.absensi.izin-index', compact('items'));
    }
    public function izinCreate()
    {
        return view('tu.absensi.izin-create');
    }
    public function izinStore(Request $request)
    {
        $request->validate([
            'tanggal'    => 'required|date',
            'jenis'      => 'required|in:izin,sakit',
            'keterangan' => 'nullable|string|max:500',
        ]);

        Izin::create([
            'user_id'    => Auth::id(),
            'tanggal'    => $request->tanggal,
            'jenis'      => $request->jenis,
            'keterangan' => $request->keterangan,
            'status'     => 'pending',
        ]);

        return redirect()->route('tu.absensi.izinIndex')->with('success','Permohonan izin dikirim.');
    }
    public function izinShow(Izin $izin)
    {
        abort_if($izin->user_id !== Auth::id(), 403);
        return view('tu.absensi.izin-show', compact('izin'));
    }

    // ------ helper jarak haversine --------
    private function jarak($lat1,$lng1,$lat2,$lng2): float
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
        return 2*$R*atan2(sqrt($a), sqrt(1-$a));
    }
}
