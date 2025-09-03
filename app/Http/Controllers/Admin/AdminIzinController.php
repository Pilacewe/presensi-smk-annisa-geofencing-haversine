<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Izin;
use App\Models\Presensi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class AdminIzinController extends Controller
{
    /**
     * List izin + filter + pagination.
     */
    public function index(Request $r)
    {
        $status = $r->input('status');          // pending|approved|rejected|null
        $jenis  = $r->input('jenis');           // izin|sakit|null
        $q      = trim((string) $r->input('q')); // cari nama/keterangan

        $items = Izin::with('user:id,name')
            ->when($status, fn($x) => $x->where('status', $status))
            ->when($jenis,  fn($x) => $x->where('jenis',  $jenis))
            ->when($q, function ($x) use ($q) {
                $x->whereHas('user', fn($u) => $u->where('name','like',"%{$q}%"))
                  ->orWhere('keterangan','like',"%{$q}%");
            })
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        // tambahkan URL bukti supaya gampang dipakai di blade
        $items->getCollection()->transform(function ($row) {
        // gunakan helper asset() supaya aman meskipun APP_URL belum diset
        $row->bukti_url = $row->bukti ? asset('storage/'.$row->bukti) : null;
        return $row;
    });


        $summary = [
            'pending'  => Izin::where('status','pending')->count(),
            'approved' => Izin::where('status','approved')->count(),
            'rejected' => Izin::where('status','rejected')->count(),
        ];

        return view('admin.izin.index', compact('items','status','jenis','q','summary'));
    }

    /**
     * Setujui izin + turunkan ke presensis (1 baris / tanggal, tidak menimpa).
     */
    public function approve(Izin $izin)
    {
        if ($izin->status === 'approved') {
            return back()->with('message','Izin sudah disetujui.');
        }

        $izin->update([
            'status'        => 'approved',
            'approved_by'   => auth()->id(),
            'approved_at'   => now(),
            'reject_reason' => null,
        ]);

        $this->applyIzinToPresensi($izin);

        return back()->with('success','Izin disetujui & presensi dibuat.');
    }

    /**
     * Tolak izin (opsional alasan).
     */
    public function reject(Request $r, Izin $izin)
    {
        $r->validate(['reject_reason' => 'nullable|string|max:255']);

        $izin->update([
            'status'        => 'rejected',
            'approved_by'   => auth()->id(),
            'approved_at'   => now(),
            'reject_reason' => $r->input('reject_reason'),
        ]);

        // Tidak menyentuh presensi di sini.
        return back()->with('success','Izin ditolak.');
    }

    /**
     * Hapus izin + bersihkan presensi otomatis yang dibuat saat approve.
     */
    public function destroy(Izin $izin)
    {
        $start  = Carbon::parse($izin->tgl_mulai, config('app.timezone'))->toDateString();
        $end    = Carbon::parse($izin->tgl_selesai ?: $izin->tgl_mulai, config('app.timezone'))->toDateString();
        $status = $izin->jenis === 'sakit' ? 'sakit' : 'izin';

        // hapus hanya presensi otomatis (jam_masuk/keluar NULL)
        Presensi::where('user_id', $izin->user_id)
            ->whereBetween('tanggal', [$start, $end])
            ->where('status', $status)
            ->whereNull('jam_masuk')
            ->whereNull('jam_keluar')
            ->delete();

        if ($izin->bukti && Storage::disk('public')->exists($izin->bukti)) {
            Storage::disk('public')->delete($izin->bukti);
        }

        $izin->delete();

        return back()->with('success','Izin dan presensi otomatisnya sudah dihapus.');
    }

    public function bukti(Izin $izin)
{
    // Kolom $izin->bukti harus menyimpan path relatif di disk 'public', 
    // mis: 'izin_bukti/abc123.jpg' atau 'izin_bukti/surat.pdf'
    if (!$izin->bukti || !Storage::disk('public')->exists($izin->bukti)) {
        abort(404);
    }

    // Stream file dengan Content-Type yang benar (image/jpeg, application/pdf, dll)
    return Storage::disk('public')->response($izin->bukti);
}

    /**
     * Generator presensi izin/sakit (aman, tidak menimpa yang sudah ada).
     */
    private function applyIzinToPresensi(Izin $izin): void
    {
        $status = $izin->jenis === 'sakit' ? 'sakit' : 'izin';

        $start = Carbon::parse($izin->tgl_mulai, config('app.timezone'))->startOfDay();
        $end   = Carbon::parse($izin->tgl_selesai ?: $izin->tgl_mulai, config('app.timezone'))->startOfDay();
        if ($end->lt($start)) $end = $start->copy();

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $tanggal = $d->toDateString();

            $exists = Presensi::where('user_id', $izin->user_id)
                        ->whereDate('tanggal', $tanggal)
                        ->exists();
            if ($exists) continue;

            Presensi::create([
                'user_id'     => $izin->user_id,
                'tanggal'     => $tanggal,
                'status'      => $status,       // izin atau sakit
                'jam_masuk'   => null,
                'jam_keluar'  => null,
                'telat_menit' => null,
                'latitude'    => null,
                'longitude'   => null,
            ]);
        }
    }
}
