<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\PiketRoster;
use App\Models\Presensi;
use App\Models\Izin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PiketDashboardController extends Controller
{
    public function index()
    {
        $tz    = config('app.timezone','Asia/Jakarta');
        $now   = Carbon::now($tz);
        $today = $now->toDateString();

        // ===== Roster hari ini =====
        $rosterToday = PiketRoster::with('user:id,name')
            ->whereDate('tanggal', $today)
            ->first();

        // ===== Siapa saja yang wajib absen? (Guru aktif) =====
        $wajibQuery = User::where('role','guru')->where('is_active',1);
        $wajibIds   = $wajibQuery->pluck('id');
        $totalGuru  = $wajibIds->count();

        // ===== Presensi hari ini untuk guru aktif =====
        $presensiToday = Presensi::with('user:id,name')
            ->whereDate('tanggal',$today)
            ->whereIn('user_id',$wajibIds)
            ->get();

        $hadir = $presensiToday->where('status','hadir')->count();
        $telat = $presensiToday->where('status','telat')->count();

        // ===== Izin/Sakit hari ini (approved & overlap tanggal) =====
        $izinToday = Izin::with('user:id,name')
            ->where('status','approved')
            ->whereIn('jenis',['izin','sakit'])
            ->whereDate('tgl_mulai','<=',$today)
            ->whereDate('tgl_selesai','>=',$today)
            ->whereIn('user_id',$wajibIds)
            ->get(['id','user_id','jenis','tgl_mulai','tgl_selesai']);

        $izin  = $izinToday->where('jenis','izin')->count();
        $sakit = $izinToday->where('jenis','sakit')->count();

        // ===== Hitung "Belum Absen" (tidak hadir/telat & tidak izin/sakit) =====
        $presentIds    = $presensiToday->whereIn('status',['hadir','telat'])->pluck('user_id')->unique();
        $dispensasiIds = $izinToday->pluck('user_id')->unique();
        $coveredIds    = $presentIds->merge($dispensasiIds)->unique();   // yang sudah "tercover"
        $belumIds      = $wajibIds->diff($coveredIds);                   // sisanya = belum absen
        $belum         = $belumIds->count();

        // ===== Daftar untuk panel samping =====
        $belumList = User::whereIn('id',$belumIds)
            ->orderBy('name')
            ->take(7)
            ->get(['id','name']);

        $hadirList = $presensiToday->where('status','hadir')
            ->sortBy('jam_masuk')
            ->take(7)
            ->values();

        $telatList = $presensiToday->where('status','telat')
            ->sortByDesc('telat_menit')
            ->take(7)
            ->values();

        // (opsional) list izin/sakit untuk panel
        $izinList = $izinToday->sortBy('user.name')->take(7)->values();

        // ===== Aktivitas terbaru (10) =====
        $recent = Presensi::with('user:id,name')
            ->whereDate('tanggal',$today)
            ->orderByDesc(DB::raw('COALESCE(jam_keluar, jam_masuk, updated_at)'))
            ->limit(10)
            ->get(['id','user_id','tanggal','status','jam_masuk','jam_keluar','telat_menit','updated_at']);

        // ===== Progress bar (hadir + telat) =====
        $pct = $totalGuru > 0 ? round((($hadir + $telat) / $totalGuru) * 100) : 0;

        // ===== Dropdown pegawai untuk set petugas (guru/tu/kepsek aktif) =====
        $pegawai = User::whereIn('role',['guru','tu','kepsek'])
            ->where('is_active',1)
            ->orderBy('name')
            ->get(['id','name']);

        return view('piket.dashboard', [
            'rosterToday' => $rosterToday,
            'pegawai'     => $pegawai,

            'totalGuru'   => $totalGuru,
            'hadir'       => $hadir,
            'telat'       => $telat,
            'izin'        => $izin,
            'sakit'       => $sakit,
            'belum'       => $belum,
            'pct'         => $pct,

            'recent'      => $recent,
            'hadirList'   => $hadirList,
            'telatList'   => $telatList,
            'belumList'   => $belumList,
            'izinList'    => $izinList,
        ]);
    }

    /**
     * Set petugas piket hari ini (sehari penuh, tanpa shift).
     */
    public function startShift(Request $r)
    {
        $tz    = config('app.timezone','Asia/Jakarta');
        $today = Carbon::now($tz)->toDateString();

        $data = $r->validate([
            'user_id' => ['required','exists:users,id'],
            'catatan' => ['nullable','string','max:200'],
        ]);

        PiketRoster::updateOrCreate(
            ['tanggal' => $today],
            [
                'user_id'    => $data['user_id'],
                'catatan'    => $data['catatan'] ?? null,
                'assigned_by'=> auth()->id(),
            ]
        );

        return back()->with('success','Petugas piket untuk hari ini disimpan.');
    }
}
