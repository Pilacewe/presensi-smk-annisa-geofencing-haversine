<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Presensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresensiController extends Controller
{
    /** =================== HELPERS WAKTU =================== */
    private function nowWIB(): Carbon
    {
        return Carbon::now(config('app.timezone', 'Asia/Jakarta'));
    }

    private function within(string $from, string $to, Carbon $now): bool
    {
        return $now->between(
            $now->copy()->setTimeFromTimeString($from),
            $now->copy()->setTimeFromTimeString($to)
        );
    }

    private function after(string $from, Carbon $now): bool
    {
        return $now->greaterThanOrEqualTo($now->copy()->setTimeFromTimeString($from));
    }

    /** =================== DASHBOARD PEGAWAI =================== */
    public function index()
    {
        $user  = Auth::user();
        $today = $this->nowWIB()->toDateString();

        // Statistik pribadi
        $stat = [
            'hadir' => Presensi::where('user_id', $user->id)->where('status', 'hadir')->count(),
            'sakit' => Presensi::where('user_id', $user->id)->where('status', 'sakit')->count(),
            'izin'  => Presensi::where('user_id', $user->id)->where('status', 'izin')->count(),
        ];

        // Data presensi hari ini (untuk UI)
        $todayRecord = Presensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->first();

        return view('presensi.index', compact('stat', 'todayRecord'));
    }

    /** =================== FORM KONFIRMASI + PETA =================== */
    public function formMasuk()  { return $this->renderForm('masuk'); }
    public function formKeluar() { return $this->renderForm('keluar'); }

    private function renderForm(string $mode)
    {
        $now = $this->nowWIB();

        $allowMasuk  = $this->within(config('presensi.jam_masuk_start'), config('presensi.jam_masuk_end'), $now);
        $allowKeluar = $this->after(config('presensi.jam_keluar_start'), $now);

        $base = [
            'lat'    => (float) config('presensi.lat'),
            'lng'    => (float) config('presensi.lng'),
            'radius' => (float) config('presensi.radius'),
        ];

        return view('presensi.form', [
            'mode'          => $mode,
            'now'           => $now,
            'allowMasuk'    => $allowMasuk,
            'allowKeluar'   => $allowKeluar,
            'deadlineMasuk' => config('presensi.jam_masuk_end'),
            'mulaiKeluar'   => config('presensi.jam_keluar_start'),
            'base'          => $base,
        ]);
    }

    /** =================== SIMPAN MASUK / KELUAR =================== */
    public function storeMasuk(Request $request)  { return $this->storeGeneric($request, 'masuk'); }
    public function storeKeluar(Request $request) { return $this->storeGeneric($request, 'keluar'); }

    private function storeGeneric(Request $request, string $mode)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $now = $this->nowWIB();

        // Validasi waktu
        if ($mode === 'masuk') {
            if (! $this->within(config('presensi.jam_masuk_start'), config('presensi.jam_masuk_end'), $now)) {
                return back()->with('message', 'Presensi masuk hanya dibuka pukul '
                    .config('presensi.jam_masuk_start').'–'.config('presensi.jam_masuk_end').'.');
            }
        } else { // keluar
            if (! $this->after(config('presensi.jam_keluar_start'), $now)) {
                return back()->with('message', 'Presensi keluar baru dapat dilakukan mulai pukul '
                    .config('presensi.jam_keluar_start').'.');
            }
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
        $today  = $now->toDateString();

        $record = Presensi::firstOrCreate(
            ['user_id' => $user->id, 'tanggal' => $today],
            ['status'  => 'hadir'] // default saat pertama kali presensi
        );

        if ($mode === 'masuk') {
            if ($record->jam_masuk) {
                return back()->with('message', 'Presensi MASUK sudah tercatat.');
            }
            $record->update([
                'jam_masuk' => $now->format('H:i:s'),
                'latitude'  => $latUser,
                'longitude' => $lngUser,
                'status'    => 'hadir',
            ]);
            return redirect()->route('presensi.index')->with('success', 'Presensi MASUK berhasil!');
        }

        // keluar
        if (! $record->jam_masuk) return back()->with('message', 'Silakan presensi MASUK terlebih dahulu.');
        if ($record->jam_keluar)  return back()->with('message', 'Presensi KELUAR sudah tercatat.');

        $record->update([
            'jam_keluar' => $now->format('H:i:s'),
            'latitude'   => $latUser,   // simpan lokasi terakhir saat keluar
            'longitude'  => $lngUser,
        ]);

        return redirect()->route('presensi.index')->with('success', 'Presensi KELUAR berhasil!');
    }

    /** =================== RIWAYAT & IZIN =================== */
    public function riwayat(Request $request)
    {
        $userId = Auth::id();

        $tahun  = $request->input('tahun', $this->nowWIB()->year);
        $bulan  = $request->input('bulan', $this->nowWIB()->month);
        $status = $request->input('status'); // null/hadir/izin/sakit/alfa

        $q = Presensi::where('user_id', $userId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->orderByDesc('tanggal');

        if ($status) $q->where('status', $status);

        $data = $q->paginate(12)->withQueryString();

        $listTahun = range($this->nowWIB()->year-3, $this->nowWIB()->year);
        $listBulan = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
        ];

        return view('presensi.riwayat', compact('data','tahun','bulan','status','listTahun','listBulan'));
    }

    public function izin()
    {
        return view('presensi.izin');
    }

    /** =================== UTIL: Haversine =================== */
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
