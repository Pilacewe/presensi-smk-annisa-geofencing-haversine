<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Izin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class TuSelfPresensiController extends Controller
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

    /** Haversine, hasil meter */
    private function jarak($lat1, $lng1, $lat2, $lng2): float
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
        return 2 * $R * atan2(sqrt($a), sqrt(1-$a));
    }

    /* ===================== Helpers waktu (aman) ===================== */
    /** Ambil jam dari config, fallback ke default jika kosong/tidak valid. Format yang diterima: HH:MM */
    private function timeOr(string $cfgKey, string $fallback): string
    {
        $val = (string) config($cfgKey, $fallback);
        $val = trim($val);
        return preg_match('/^\d{2}:\d{2}$/', $val) ? $val : $fallback;
    }

    /** Ubah "HH:MM" menjadi Carbon pada tanggal $base (detik=00) */
    private function atToday(string $hhmm, Carbon $base): Carbon
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm . ':'));
        return $base->copy()->setTime($h, $m, 0);
    }

    /* ===================== Halaman utama Presensi TU ===================== */
    public function index(Request $request)
    {
        $tab  = $request->input('tab', 'absen'); // absen|riwayat|izin
        $user = Auth::user();
        $now  = $this->nowWIB();
        $base = $this->base();

        // Window dari config
        $mulaiMasuk   = $this->timeOr('presensi.jam_masuk_start',  '07:00');
        $akhirMasuk   = $this->timeOr('presensi.jam_masuk_end',    '08:00');
        $mulaiKeluar  = $this->timeOr('presensi.jam_keluar_start', '16:00');
        $akhirKeluar  = $this->timeOr('presensi.jam_keluar_end',   '17:00');

        // Konversi ke Carbon pada tanggal hari ini
        $inStart  = $this->atToday($mulaiMasuk,  $now);
        $inEnd    = $this->atToday($akhirMasuk,  $now);
        $outStart = $this->atToday($mulaiKeluar, $now);
        $outEnd   = $this->atToday($akhirKeluar, $now);

        // Flag tombol (untuk UI)
        $allowMasuk  = $now->between($inStart,  $inEnd);
        $allowKeluar = $now->between($outStart, $outEnd);

        // Record hari ini (untuk men-disable tombol jika sudah absen)
        $todayRec = Presensi::where('user_id', $user->id)
            ->where('tanggal', $now->toDateString())
            ->first();

        // Ringkasan jumlah izin saya (badge kecil)
        $izinCount = Izin::where('user_id', $user->id)->count();

        /* =========================================================
         * TAB: RIWAYAT
         * =======================================================*/
        if ($tab === 'riwayat') {
            $tz   = config('app.timezone','Asia/Jakarta');
            $nowT = now($tz);
            $tahun  = (int) $request->input('tahun', $nowT->year);
            $bulan  = (int) $request->input('bulan', $nowT->month);
            $status = $request->input('status'); // null|hadir|telat|izin|sakit

            $from = Carbon::create($tahun,$bulan,1,0,0,0,$tz)->startOfDay();
            $to   = $from->copy()->endOfMonth()->endOfDay();
            $uid  = $user->id;

            // 1) Presensi harian (kecualikan izin/sakit)
            $harian = Presensi::where('user_id',$uid)
                ->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()])
                ->whereNotIn('status',['izin','sakit'])
                ->when(in_array($status,['hadir','telat']), fn($q)=>$q->where('status',$status))
                ->orderByDesc('tanggal')->orderByDesc('jam_masuk')
                ->get()
                ->map(function($p){
                    return (object)[
                        'type'         => 'presensi',
                        'date_start'   => $p->tanggal,
                        'date_end'     => $p->tanggal,
                        'date_label'   => Carbon::parse($p->tanggal)->translatedFormat('l, d F Y'),
                        'jam_masuk'    => $p->jam_masuk,
                        'jam_keluar'   => $p->jam_keluar,
                        'status_key'   => $p->status ?: 'hadir',
                        'status_label' => strtoupper($p->status ?: 'hadir'),
                        'telat_menit'  => $p->telat_menit,
                        'keterangan'   => null,
                        'approval'     => null,
                    ];
                });

            // 2) Izin/Sakit sebagai rentang (clip ke bulan)
            $rentang = Izin::where('user_id',$uid)
                ->where(function($q) use($from,$to){
                    $q->whereBetween('tgl_mulai',   [$from->toDateString(), $to->toDateString()])
                      ->orWhereBetween('tgl_selesai',[$from->toDateString(), $to->toDateString()])
                      ->orWhere(function($qq) use($from,$to){
                          $qq->where('tgl_mulai','<=',$from->toDateString())
                             ->where('tgl_selesai','>=',$to->toDateString());
                      });
                })
                ->when(in_array($status,['izin','sakit']), fn($q)=>$q->where('jenis',$status))
                ->orderByDesc('tgl_mulai')
                ->get()
                ->map(function($i) use($from,$to){
                    $s = Carbon::parse($i->tgl_mulai)->max($from);
                    $e = Carbon::parse($i->tgl_selesai ?: $i->tgl_mulai)->min($to);

                    return (object)[
                        'type'         => 'izin',
                        'date_start'   => $s->toDateString(),
                        'date_end'     => $e->toDateString(),
                        'date_label'   => $s->equalTo($e)
                            ? $s->translatedFormat('l, d F Y')
                            : $s->translatedFormat('d M Y').' – '.$e->translatedFormat('d M Y'),
                        'jam_masuk'    => null,
                        'jam_keluar'   => null,
                        'status_key'   => strtolower($i->jenis ?? 'izin'),   // izin|sakit
                        'status_label' => strtoupper($i->jenis ?? 'izin'),
                        'telat_menit'  => null,
                        'keterangan'   => $i->keterangan,
                        'approval'     => $i->status, // approved|pending|rejected
                    ];
                });

            // 3) Gabung + sort + paginate (Collection)
            $timeline = $harian->concat($rentang)
                ->sortByDesc(fn($x)=>[$x->date_start, $x->type])
                ->values();

            $perPage = 20;
            $page    = LengthAwarePaginator::resolveCurrentPage() ?: 1;
            $items   = $timeline->slice(($page - 1) * $perPage, $perPage)->values();
            $data    = new LengthAwarePaginator($items, $timeline->count(), $perPage, $page);
            $data->withPath(url()->current())->appends($request->query());

            // dropdowns
            $years    = range($nowT->year, $nowT->year - 4);
            $bulanMap = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];

            return view('tu.absensi.riwayat', [
                'data'     => $data,
                'years'    => $years,
                'bulanMap' => $bulanMap,
                'tahun'    => $tahun,
                'bulan'    => $bulan,
                'status'   => $status,
            ]);
        }

        /* =========================================================
         * TAB: IZIN (pribadi)
         * =======================================================*/
        if ($tab === 'izin') {
            $izinItems = Izin::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate(10)
                ->withQueryString();

            return view('tu.absensi.index', [
                'tab'          => 'izin',
                'now'          => $now,
                'base'         => $base,
                'mulaiMasuk'   => $mulaiMasuk,
                'akhirMasuk'   => $akhirMasuk,
                'mulaiKeluar'  => $mulaiKeluar,
                'akhirKeluar'  => $akhirKeluar,
                'allowMasuk'   => $allowMasuk,
                'allowKeluar'  => $allowKeluar,
                'todayRec'     => $todayRec,
                'izinItems'    => $izinItems,
                'izinCount'    => $izinCount,
                // 'data' tidak dipakai pada tab izin
            ]);
        }

        /* =========================================================
         * TAB: ABSEN (default)
         * =======================================================*/
        return view('tu.absensi.index', [
            'tab'          => 'absen',
            'now'          => $now,
            'base'         => $base,
            'mulaiMasuk'   => $mulaiMasuk,
            'akhirMasuk'   => $akhirMasuk,
            'mulaiKeluar'  => $mulaiKeluar,
            'akhirKeluar'  => $akhirKeluar,
            'allowMasuk'   => $allowMasuk,
            'allowKeluar'  => $allowKeluar,
            'todayRec'     => $todayRec,
            'izinCount'    => $izinCount,
        ]);
    }

    /* ===================== Form & Store MASUK ===================== */
    public function formMasuk()
    {
        $now        = $this->nowWIB();
        $base       = $this->base();
        $mulaiMasuk = $this->timeOr('presensi.jam_masuk_start', '07:00');
        $akhirMasuk = $this->timeOr('presensi.jam_masuk_end',   '08:00');

        $allowMasuk = $now->between(
            $this->atToday($mulaiMasuk, $now),
            $this->atToday($akhirMasuk, $now)
        );

        return view('tu.absensi.form', [
            'mode'          => 'masuk',
            'now'           => $now,
            'base'          => $base,
            'deadlineMasuk' => $akhirMasuk,
            'mulaiKeluar'   => null,
            'allowMasuk'    => $allowMasuk,
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
            ['status'  => 'hadir']
        );

        if ($rec->jam_masuk) {
            return back()->with('message', 'Presensi MASUK sudah tercatat.');
        }

        // hitung telat dari jam target
        $targetStr   = $this->timeOr('presensi.jam_target_masuk','07:00');
        $targetMasuk = $now->copy()->setTimeFromTimeString($targetStr);
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

        return redirect()->route('tu.absensi.index')->with('success', $msg);
    }

    /* ===================== Form & Store KELUAR ===================== */
    public function formKeluar()
    {
        $now         = $this->nowWIB();
        $base        = $this->base();
        $mulaiKeluar = $this->timeOr('presensi.jam_keluar_start', '16:00');

        $allowKeluar = $now->greaterThanOrEqualTo($this->atToday($mulaiKeluar, $now));

        return view('tu.absensi.form', [
            'mode'          => 'keluar',
            'now'           => $now,
            'base'          => $base,
            'deadlineMasuk' => null,
            'mulaiKeluar'   => $mulaiKeluar,
            'allowMasuk'    => null,
            'allowKeluar'   => $allowKeluar,
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

        if ($now->lt($this->atToday($mulaiKeluar, $now))) {
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

        $rec = Presensi::where('user_id', $user->id)->where('tanggal', $today)->first();

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

        return redirect()->route('tu.absensi.index')->with('success', 'Presensi KELUAR berhasil!');
    }

    /* ===================== Izin TU (pribadi) ===================== */
    public function izinIndex()
    {
        $items = Izin::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('tu.absensi.izin-index', compact('items'));
    }

    public function izinCreate()
    {
        return view('tu.absensi.izin-create');
    }

    public function izinStore(Request $request)
    {
        $request->validate([
            'tgl_mulai'   => 'required|date',
            'tgl_selesai' => 'nullable|date|after_or_equal:tgl_mulai',
            'jenis'       => 'required|in:izin,sakit',
            'keterangan'  => 'nullable|string|max:500',
        ]);

        Izin::create([
            'user_id'     => Auth::id(),
            'tgl_mulai'   => $request->tgl_mulai,
            'tgl_selesai' => $request->tgl_selesai ?: $request->tgl_mulai,
            'jenis'       => $request->jenis,
            'keterangan'  => $request->keterangan,
            'status'      => 'pending',
        ]);

        return redirect()
            ->route('tu.absensi.index', ['tab' => 'izin'])
            ->with('success', 'Permohonan izin dikirim.');
    }

    public function izinShow(Izin $izin)
    {
        abort_if($izin->user_id !== Auth::id(), 403);
        return view('tu.absensi.izin-show', compact('izin'));
    }
}
