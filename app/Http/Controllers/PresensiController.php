<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\Presensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresensiController extends Controller
{
    /**
     * Halaman utama presensi (UI pegawai).
     * Kirim statistik + record hari ini agar tombol bisa enable/disable.
     */
    public function index()
    {
        $user  = Auth::user();
        $today = now()->toDateString();

        // Statistik pribadi
        $stat = [
            'hadir' => Presensi::where('user_id', $user->id)->where('status', 'hadir')->count(),
            'sakit' => Presensi::where('user_id', $user->id)->where('status', 'sakit')->count(),
            'izin'  => Presensi::where('user_id', $user->id)->where('status', 'izin')->count(),
        ];

        // Data presensi hari ini (untuk disable tombol masuk/keluar)
        $todayRecord = Presensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->first();

        return view('presensi.index', compact('stat', 'todayRecord'));
    }

    /**
     * Simpan presensi MASUK/KELUAR.
     * Form harus mengirim 'mode' = masuk|keluar + latitude + longitude.
     * Termasuk validasi geofence (radius).
     */
    public function store(Request $request)
    {
        $request->validate([
            'mode'      => 'required|in:masuk,keluar',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // ===== Geofence: validasi jarak dari titik base =====
        $latUser = (float) $request->latitude;
        $lngUser = (float) $request->longitude;

        $latBase = (float) config('presensi.lat');
        $lngBase = (float) config('presensi.lng');
        $radius  = (float) config('presensi.radius'); // meter

        $jarak = $this->distanceMeters($latUser, $lngUser, $latBase, $lngBase);
        if ($jarak > $radius) {
            return back()->with('message', 'Di luar area presensi (± '.number_format($jarak,0).' m).');
        }
        // ====================================================

        $user  = Auth::user();
        $today = now()->toDateString();

        // Ambil / buat baris hari ini
        $presensi = Presensi::firstOrCreate(
            ['user_id' => $user->id, 'tanggal' => $today],
            ['status'  => 'hadir'] // default status saat pertama kali presensi (masuk)
        );

        if ($request->mode === 'masuk') {
            if ($presensi->jam_masuk) {
                return back()->with('message', 'Presensi MASUK sudah tercatat.');
            }

            $presensi->update([
                'jam_masuk' => now()->format('H:i:s'),
                'latitude'  => $latUser,
                'longitude' => $lngUser,
                'status'    => 'hadir',
            ]);

            return back()->with('success', 'Presensi MASUK berhasil!');
        }

        // mode = keluar
        if ($presensi->jam_keluar) {
            return back()->with('message', 'Presensi KELUAR sudah tercatat.');
        }

        if (!$presensi->jam_masuk) {
            return back()->with('message', 'Silakan presensi MASUK terlebih dahulu.');
        }

        $presensi->update([
            'jam_keluar' => now()->format('H:i:s'),
            'latitude'   => $latUser,   // simpan lokasi terakhir saat keluar
            'longitude'  => $lngUser,
        ]);

        return back()->with('success', 'Presensi KELUAR berhasil!');
    }

    /**
     * Riwayat presensi pribadi + filter (tahun/bulan/status).
     */
    public function riwayat(Request $request)
    {
        $userId = Auth::id();

        $tahun  = $request->input('tahun', now()->year);
        $bulan  = $request->input('bulan', now()->month);
        $status = $request->input('status'); // null/hadir/izin/sakit/alfa

        $q = Presensi::where('user_id', $userId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->orderByDesc('tanggal');

        if ($status) $q->where('status', $status);

        $data = $q->paginate(12)->withQueryString();

        $listTahun = range(now()->year-3, now()->year);
        $listBulan = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
        ];

        return view('presensi.riwayat', compact('data','tahun','bulan','status','listTahun','listBulan'));
    }

    /**
     * Form izin/sakit (placeholder – nanti tambah upload bukti & approval admin).
     */
    public function izin()
    {
        return view('presensi.izin');
    }

    /* ====== FORM KONFIRMASI (MENAMPILKAN PETA + CEK JAM) ====== */
public function formMasuk()  { return $this->renderForm('masuk'); }
public function formKeluar() { return $this->renderForm('keluar'); }

private function renderForm(string $mode)
{
    $now = Carbon::now();

    // Ambil batas jam dari .env
    $deadlineMasuk = env('PRESENSI_MASUK_DEADLINE', '07:00');
    $mulaiKeluar   = env('PRESENSI_KELUAR_START',   '16:00');

    $allowMasuk  = $now->lte(Carbon::createFromTimeString($deadlineMasuk));
    $allowKeluar = $now->gte(Carbon::createFromTimeString($mulaiKeluar));

    // base geofence untuk peta
    $base = [
        'lat'    => (float) config('presensi.lat'),
        'lng'    => (float) config('presensi.lng'),
        'radius' => (float) config('presensi.radius'),
        'apiKey' => env('GOOGLE_MAPS_API_KEY'),
    ];

    return view('presensi.form', compact(
        'mode','now','deadlineMasuk','mulaiKeluar','allowMasuk','allowKeluar','base'
    ));
}

/* ====== SIMPAN MASUK / KELUAR DENGAN VALIDASI JAM & GEOFENCE ====== */
public function storeMasuk(Request $request)  { return $this->storeGeneric($request, 'masuk'); }
public function storeKeluar(Request $request) { return $this->storeGeneric($request, 'keluar'); }

private function storeGeneric(Request $request, string $mode)
{
    $request->validate([
        'latitude'  => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    $now = Carbon::now();
    $deadlineMasuk = env('PRESENSI_MASUK_DEADLINE', '07:00');
    $mulaiKeluar   = env('PRESENSI_KELUAR_START',   '16:00');

    if ($mode === 'masuk' && $now->gt(Carbon::createFromTimeString($deadlineMasuk))) {
        return back()->with('message', 'Batas presensi masuk pukul '.$deadlineMasuk);
    }
    if ($mode === 'keluar' && $now->lt(Carbon::createFromTimeString($mulaiKeluar))) {
        return back()->with('message', 'Presensi keluar baru bisa mulai pukul '.$mulaiKeluar);
    }

    // Geofence (jarak)
    $latUser = (float) $request->latitude;
    $lngUser = (float) $request->longitude;
    $latBase = (float) config('presensi.lat');
    $lngBase = (float) config('presensi.lng');
    $radius  = (float) config('presensi.radius');
    $jarak   = $this->distanceMeters($latUser, $lngUser, $latBase, $lngBase);

    if ($jarak > $radius) {
        return back()->with('message', 'Di luar area presensi (± '.number_format($jarak,0).' m).');
    }

    // Simpan
    $user   = Auth::user();
    $today  = now()->toDateString();
    $record = Presensi::firstOrCreate(
        ['user_id' => $user->id, 'tanggal' => $today],
        ['status'  => 'hadir']
    );

    if ($mode === 'masuk') {
        if ($record->jam_masuk) return back()->with('message', 'Presensi MASUK sudah tercatat.');
        $record->update([
            'jam_masuk' => now()->format('H:i:s'),
            'latitude'  => $latUser,
            'longitude' => $lngUser,
            'status'    => 'hadir',
        ]);
        return redirect()->route('presensi.index')->with('success', 'Presensi MASUK berhasil!');
    }

    // keluar
    if (!$record->jam_masuk) return back()->with('message', 'Silakan presensi MASUK terlebih dahulu.');
    if ($record->jam_keluar) return back()->with('message', 'Presensi KELUAR sudah tercatat.');

    $record->update([
        'jam_keluar' => now()->format('H:i:s'),
        'latitude'   => $latUser,
        'longitude'  => $lngUser,
    ]);

    return redirect()->route('presensi.index')->with('success', 'Presensi KELUAR berhasil!');
}


    /** Hitung jarak 2 titik (meter) – Haversine */
    private function distanceMeters($lat1, $lng1, $lat2, $lng2): float
    {
        $R = 6371000; // meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 +
             cos(deg2rad($lat1))*cos(deg2rad($lat2)) *
             sin($dLng/2)**2;
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }
}
