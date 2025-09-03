<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Izin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PresensiController extends Controller
{
    /* ===================== Helpers umum ===================== */
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

    /** Haversine -> meter */
    private function jarak($lat1, $lng1, $lat2, $lng2): float
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
        return 2 * $R * atan2(sqrt($a), sqrt(1-$a));
    }

    /* ===================== Helpers waktu ===================== */
    private function timeOr(string $cfgKey, string $fallback): string
    {
        $val = (string) config($cfgKey, $fallback);
        $val = trim($val);
        return preg_match('/^\d{2}:\d{2}$/', $val) ? $val : $fallback;
    }

    private function atDate(string $hhmm, Carbon $base): Carbon
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm . ':'));
        return $base->copy()->setTime($h, $m, 0);
    }

    /* ===================== Landing (pegawai) ===================== */
    public function index(Request $request)
    {
        $user = Auth::user();
        $now  = $this->nowWIB();

        // Konfigurasi jam
        $mulaiMasuk   = $this->timeOr('presensi.jam_masuk_start',  '07:00');
        // HAPUS batas akhir untuk MASUK: biar tidak “tutup” jam 09:00
        $mulaiKeluar  = $this->timeOr('presensi.jam_keluar_start', '16:00');
        $akhirKeluar  = $this->timeOr('presensi.jam_keluar_end',   '18:00');

        $inStart  = $this->atDate($mulaiMasuk,  $now);
        $outStart = $this->atDate($mulaiKeluar, $now);
        $outEnd   = $this->atDate($akhirKeluar, $now);

        // catatan hari ini
        $todayRec = Presensi::where('user_id', $user->id)
            ->where('tanggal', $now->toDateString())
            ->first();

        // FLAG tombol
        $allowMasuk  = $now->greaterThanOrEqualTo($inStart) && empty($todayRec?->jam_masuk);
        $allowKeluar = $now->between($outStart, $outEnd) &&
                       !empty($todayRec?->jam_masuk) &&
                       empty($todayRec?->jam_keluar);

        // ===================== STATISTIK BULAN BERJALAN =====================
        $startMonth = $now->copy()->startOfMonth()->toDateString();
        $endMonth   = $now->copy()->endOfMonth()->toDateString();

        $overlap = function ($q) use ($startMonth, $endMonth) {
            $q->whereBetween('tgl_mulai',   [$startMonth, $endMonth])
              ->orWhereBetween('tgl_selesai',[$startMonth, $endMonth])
              ->orWhere(function($qq) use ($startMonth, $endMonth) {
                  $qq->where('tgl_mulai','<=',$startMonth)
                     ->where('tgl_selesai','>=',$endMonth);
              });
        };

        $hadir = Presensi::where('user_id',$user->id)
            ->whereBetween('tanggal', [$startMonth,$endMonth])
            ->where('status','hadir')->count();

        $telat = Presensi::where('user_id',$user->id)
            ->whereBetween('tanggal', [$startMonth,$endMonth])
            ->where('status','telat')->count();

        $izinApproved = Izin::where('user_id',$user->id)->where('status','approved')
            ->where('jenis','izin')->where($overlap)->count();

        $sakitApproved = Izin::where('user_id',$user->id)->where('status','approved')
            ->where('jenis','sakit')->where($overlap)->count();

        $stat = [
            'hadir' => $hadir,
            'telat' => $telat,
            'izin'  => $izinApproved,
            'sakit' => $sakitApproved,
        ];

        return view('presensi.index', [
            'now'          => $now,
            'base'         => $this->base(),
            'mulaiMasuk'   => $mulaiMasuk,
            // 'akhirMasuk' ditiadakan dari UX (tidak dipakai untuk menutup)
            'mulaiKeluar'  => $mulaiKeluar,
            'akhirKeluar'  => $akhirKeluar,

            'todayRecord'  => $todayRec,
            'canMasuk'     => $allowMasuk,
            'canKeluar'    => $allowKeluar,

            'todayRec'     => $todayRec,
            'allowMasuk'   => $allowMasuk,
            'allowKeluar'  => $allowKeluar,

            'stat'         => $stat,
        ]);
    }

    /* ===================== MASUK ===================== */
    public function formMasuk()
    {
        $now        = $this->nowWIB();
        $mulaiMasuk = $this->timeOr('presensi.jam_masuk_start', '07:00');
        $target     = $this->timeOr('presensi.jam_target_masuk','07:00');

        return view('presensi.form', [
            'mode'          => 'masuk',
            'now'           => $now,
            'base'          => $this->base(),
            'targetMasuk'   => $target,                 // info target (bukan tutup)
            'mulaiKeluar'   => null,
            // buka sejak jam mulai; TIDAK ada batas akhir
            'allowMasuk'    => $now->greaterThanOrEqualTo($this->atDate($mulaiMasuk, $now)),
            'allowKeluar'   => null,
        ]);
    }

    public function storeMasuk(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $now = $this->nowWIB();

        // geofence
        $jarak = $this->jarak(
            (float) $request->latitude, (float) $request->longitude,
            (float) config('presensi.lat'), (float) config('presensi.lng')
        );
        if ($jarak > (float) config('presensi.radius')) {
            return back()->with('message', 'Di luar area presensi (± ' . number_format($jarak, 0) . ' m).');
        }

        $user  = Auth::user();
        $today = $now->toDateString();

        $rec = Presensi::firstOrCreate(
            ['user_id' => $user->id, 'tanggal' => $today],
            ['status'  => 'hadir'] // seed awal
        );

        if ($rec->jam_masuk) {
            return back()->with('message', 'Presensi MASUK sudah tercatat.');
        }

        // Hitung telat dari jam target (config)
        $targetStr   = $this->timeOr('presensi.jam_target_masuk', '07:00');
        $targetMasuk = $this->atDate($targetStr, $now);
        $telatMenit  = max(0, $targetMasuk->diffInMinutes($now, false));
        $status      = $telatMenit > 0 ? 'telat' : 'hadir';

        $rec->update([
            'jam_masuk'   => $now->format('H:i:s'),
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'status'      => $status,
            'telat_menit' => $telatMenit ?: null,
        ]);

        $msg = $status === 'telat'
            ? "Presensi MASUK tersimpan. (Telat " .
              (intdiv($telatMenit,60) ? intdiv($telatMenit,60).' jam ' : '') .
              ($telatMenit%60 ? ($telatMenit%60).' menit' : (intdiv($telatMenit,60)?'':'0 menit')) . ")"
            : "Presensi MASUK tersimpan. Tepat waktu.";

        return redirect()->route('presensi.index')->with('success', $msg);
    }

    /* ===================== KELUAR ===================== */
    public function formKeluar()
    {
        $now         = $this->nowWIB();
        $mulaiKeluar = $this->timeOr('presensi.jam_keluar_start', '16:00');

        return view('presensi.form', [
            'mode'          => 'keluar',
            'now'           => $now,
            'base'          => $this->base(),
            'deadlineMasuk' => null,
            'mulaiKeluar'   => $mulaiKeluar,
            'allowMasuk'    => null,
            'allowKeluar'   => $now->greaterThanOrEqualTo($this->atDate($mulaiKeluar, $now)),
        ]);
    }

    public function storeKeluar(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $now         = $this->nowWIB();
        $mulaiKeluar = $this->timeOr('presensi.jam_keluar_start', '16:00');

        if ($now->lt($this->atDate($mulaiKeluar, $now))) {
            return back()->with('message', "Presensi keluar dibuka {$mulaiKeluar}")->withInput();
        }

        // geofence
        $jarak = $this->jarak(
            (float) $request->latitude, (float) $request->longitude,
            (float) config('presensi.lat'), (float) config('presensi.lng')
        );
        if ($jarak > (float) config('presensi.radius')) {
            return back()->with('message', 'Di luar area presensi (± ' . number_format($jarak, 0) . ' m).');
        }

        $user  = Auth::user();
        $today = $now->toDateString();

        $rec = Presensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->first();

        if (!$rec || !$rec->jam_masuk) {
            return back()->with('message', 'Silakan presensi MASUK terlebih dahulu.');
        }
        if ($rec->jam_keluar) {
            return back()->with('message', 'Presensi KELUAR sudah tercatat.');
        }

        $rec->update([
            'jam_keluar' => $now->format('H:i:s'),
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
        ]);

        return redirect()->route('presensi.index')->with('success', 'Presensi KELUAR berhasil!');
    }

    /* ===================== RIWAYAT (gabungan harian + range izin) ===================== */
    public function riwayat(Request $r)
{
    $tz   = config('app.timezone','Asia/Jakarta');
    $now  = now($tz);
    $userId = Auth::id();

    // dropdown periode
    $tahun = (int) $r->input('tahun', $now->year);
    $bulan = (int) $r->input('bulan', $now->month);
    $listTahun = range($now->year, $now->year - 4);
    $listBulan = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];

    $from = Carbon::create($tahun,$bulan,1,0,0,0,$tz)->startOfDay();
    $to   = $from->copy()->endOfMonth()->endOfDay();

    // Harian: selain izin/sakit (biar izin tidak dobel)
    $harian = Presensi::where('user_id',$userId)
        ->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()])
        ->whereNotIn('status',['izin','sakit'])
        ->orderByDesc('tanggal')
        ->get()
        ->map(function($p){
            return (object)[
                'type'        => 'presensi',
                'status'      => $p->status,
                'date_start'  => $p->tanggal,
                'date_end'    => $p->tanggal,
                'jam_masuk'   => $p->jam_masuk,
                'jam_keluar'  => $p->jam_keluar,
                'telat_menit' => $p->telat_menit,
            ];
        });

    // Izin/Sakit: sebagai rentang
    $izinRanges = Izin::where('user_id',$userId)
        ->where(function($q) use($from,$to){
            $q->whereBetween('tgl_mulai',   [$from->toDateString(), $to->toDateString()])
              ->orWhereBetween('tgl_selesai',[$from->toDateString(), $to->toDateString()])
              ->orWhere(function($qq) use($from,$to){
                  $qq->where('tgl_mulai','<=',$from->toDateString())
                     ->where('tgl_selesai','>=',$to->toDateString());
              });
        })
        ->orderByDesc('tgl_mulai')
        ->get()
        ->map(function($i) use($from,$to){
            $s = Carbon::parse($i->tgl_mulai)->max($from)->toDateString();
            $e = Carbon::parse($i->tgl_selesai)->min($to)->toDateString();
            return (object)[
                'type'       => 'izin',
                'status'     => $i->jenis,     // izin|sakit
                'date_start' => $s,
                'date_end'   => $e,
                'approval'   => $i->status,    // approved|pending|rejected
                'keterangan' => $i->keterangan,
            ];
        });

    $timeline = $harian->concat($izinRanges)
        ->sortByDesc(fn($x)=>$x->date_start)
        ->values();

    return view('presensi.riwayat', [
        'timeline'  => $timeline,
        'tahun'     => $tahun,
        'bulan'     => $bulan,
        'listTahun' => $listTahun,
        'listBulan' => $listBulan,
    ]);
}
}
