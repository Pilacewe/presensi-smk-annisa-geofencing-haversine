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
    /* ===================== Helpers ===================== */
    private function nowWIB(): Carbon
    {
        // pastikan APP_TIMEZONE=Asia/Jakarta di .env
        return Carbon::now(config('app.timezone', 'Asia/Jakarta'));
    }

    private function base(): array
    {
        // titik & radius geofence ambil dari config/presensi.php
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
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $R * atan2(sqrt($a), sqrt(1 - $a));
    }

    /* ===================== Halaman utama Presensi TU (absen/riwayat/izin) ===================== */
    public function index(Request $request)
    {
        $tab   = $request->input('tab', 'absen'); // absen|riwayat|izin
        $user  = Auth::user();
        $now   = $this->nowWIB();
        $base  = $this->base();

        // Window waktu dari config
        $mulaiMasuk   = config('presensi.jam_masuk_start',  '07:00');
        $akhirMasuk   = config('presensi.jam_masuk_end',    '08:00');
        $mulaiKeluar  = config('presensi.jam_keluar_start', '16:00');
        $akhirKeluar  = config('presensi.jam_keluar_end',   '17:00');

        // Flag izin tombol (untuk UI)
        $allowMasuk = $now->between(
            $now->copy()->setTimeFromTimeString($mulaiMasuk),
            $now->copy()->setTimeFromTimeString($akhirMasuk)
        );

        $allowKeluar = $now->between(
            $now->copy()->setTimeFromTimeString($mulaiKeluar),
            $now->copy()->setTimeFromTimeString($akhirKeluar)
        );

        // Record hari ini (disable tombol bila sudah absen)
        $todayRec = Presensi::where('user_id', $user->id)
            ->where('tanggal', $now->toDateString())
            ->first();

             // ➜ Tambahkan ini: total izin user (selalu ada untuk ringkasan)
        $izinCount = Izin::where('user_id',$user->id)->count();
        /* ------- data untuk tab RIWAYAT ------- */
        $data = collect();
        if ($tab === 'riwayat') {
            $tahun  = $request->input('tahun', $now->year);
            $bulan  = $request->input('bulan', $now->month);
            $status = $request->input('status'); // null|hadir|izin|sakit|alfa

            $q = Presensi::where('user_id', $user->id)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->orderByDesc('tanggal');

            if ($status) $q->where('status', $status);

            $data = $q->paginate(12)->withQueryString();
        }

        /* ------- data untuk tab IZIN ------- */
        $izinItems = collect();
        if ($tab === 'izin') {
            $izinItems = Izin::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate(10)
                ->withQueryString();
        }

        return view('tu.absensi.index', compact(
            'tab', 'now', 'base',
            'mulaiMasuk', 'akhirMasuk', 'mulaiKeluar', 'akhirKeluar',
            'allowMasuk', 'allowKeluar',
            'todayRec', 'data', 'izinItems', 'izinCount'
        ));
    }

    /* ===================== Form & Store MASUK ===================== */
    public function formMasuk()
    {
        $now        = $this->nowWIB();
        $base       = $this->base();
        $mulaiMasuk = config('presensi.jam_masuk_start', '07:00');
        $akhirMasuk = config('presensi.jam_masuk_end',   '08:00');

        $allowMasuk = $now->between(
            $now->copy()->setTimeFromTimeString($mulaiMasuk),
            $now->copy()->setTimeFromTimeString($akhirMasuk)
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

        $now        = $this->nowWIB();
        $mulaiMasuk = config('presensi.jam_masuk_start', '07:00');
        $akhirMasuk = config('presensi.jam_masuk_end',   '08:00');

        // validasi waktu
        if (! $now->between(
            $now->copy()->setTimeFromTimeString($mulaiMasuk),
            $now->copy()->setTimeFromTimeString($akhirMasuk)
        )) {
            return back()->with('message', "Presensi masuk hanya {$mulaiMasuk}-{$akhirMasuk}")->withInput();
        }

        // geofence
        $jarak = $this->jarak(
            (float) $request->latitude, (float) $request->longitude,
            (float) config('presensi.lat'), (float) config('presensi.lng')
        );
        if ($jarak > (float) config('presensi.radius')) {
            return back()->with('message', 'Di luar area presensi (± ' . number_format($jarak, 0) . ' m).');
        }

        // simpan
        $user  = Auth::user();
        $today = $now->toDateString();

        $rec = Presensi::firstOrCreate(
            ['user_id' => $user->id, 'tanggal' => $today],
            ['status'  => 'hadir']
        );

        if ($rec->jam_masuk) {
            return back()->with('message', 'Presensi MASUK sudah tercatat.');
        }

        $rec->update([
            'jam_masuk' => $now->format('H:i:s'),
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
            'status'    => 'hadir',
        ]);

        return redirect()->route('tu.absensi.index')->with('success', 'Presensi MASUK berhasil!');
    }

    /* ===================== Form & Store KELUAR ===================== */
    public function formKeluar()
    {
        $now         = $this->nowWIB();
        $base        = $this->base();
        $mulaiKeluar = config('presensi.jam_keluar_start', '16:00');

        $allowKeluar = $now->greaterThanOrEqualTo(
            $now->copy()->setTimeFromTimeString($mulaiKeluar)
        );

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
        $mulaiKeluar = config('presensi.jam_keluar_start', '16:00');

        // validasi waktu
        if ($now->lt($now->copy()->setTimeFromTimeString($mulaiKeluar))) {
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

        if (! $rec || ! $rec->jam_masuk) {
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
        // jika kosong, samakan dengan tgl_mulai (izin 1 hari)
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
