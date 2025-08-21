<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Izin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
namespace App\Http\Controllers\Admin;

class IzinController extends Controller
{
    public function index(Request $request)
    {
        // Filter
        $status = $request->input('status'); // pending|approved|rejected|null
        $bulan  = $request->input('bulan');  // 1..12
        $tahun  = $request->input('tahun');  // yyyy
        $userId = $request->input('user_id'); // optional

        $q = Izin::with(['user'])
            ->orderByDesc('created_at');

        if ($status) $q->where('status', $status);
        if ($bulan)  $q->whereMonth('tanggal', (int)$bulan);
        if ($tahun)  $q->whereYear('tanggal', (int)$tahun);
        if ($userId) $q->where('user_id', (int)$userId);

        $data = $q->paginate(12)->withQueryString();

        // Dropdown guru (atau semua user) — di sini contoh: semua user (urut nama)
        $users = User::orderBy('name')->get(['id','name','role']);

        // Ringkasan cepat
        $summary = [
            'pending'  => Izin::where('status','pending')->count(),
            'approved' => Izin::where('status','approved')->count(),
            'rejected' => Izin::where('status','rejected')->count(),
        ];

        // List bulan/tahun
        $listBulan = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
        ];
        $thisYear  = now()->year;
        $listTahun = range($thisYear-3, $thisYear);

        return view('admin.izin.index', compact('data','users','summary','status','bulan','tahun','userId','listBulan','listTahun'));
    }

    public function show(Izin $izin)
    {
        $izin->load('user');
        return view('admin.izin.show', compact('izin'));
    }

    public function updateStatus(Request $request, Izin $izin)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'catatan'=> 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($izin, $request) {
            $izin->status  = $request->status;
            // jika tabel punya kolom catatan_admin, simpan (opsional)
            if ($izin->isFillable('catatan_admin')) {
                $izin->catatan_admin = $request->catatan;
            }
            $izin->save();
        });

        return redirect()
            ->route('admin.izin.show', $izin)
            ->with('success', 'Status izin diperbarui menjadi '.strtoupper($request->status).'.');
    }
}

class AdminIzinController extends Controller {
    public function index() {
        return view('admin.izin.index'); // buat view sederhana dulu
    }
}

// app/Http/Controllers/Admin/AdminExportController.php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

class AdminExportController extends Controller {
    public function index() {
        return view('admin.export.index'); // buat view sederhana dulu
    }
}