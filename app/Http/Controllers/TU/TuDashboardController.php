<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use App\Models\Izin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class TuDashboardController extends Controller
{
    public function index()
{
    $tz    = config('app.timezone', 'Asia/Jakarta');
    $today = \Illuminate\Support\Carbon::now($tz)->toDateString();
    $nowH  = \Illuminate\Support\Carbon::now($tz)->format('H:i');

    // 1) ROLE
    $rolesDipantau = ['guru','tu','piket','kepsek']; // tampilkan bila perlu
    $wajibRoles    = ['guru','tu'];                  // HANYA ini yang dihitung KPI

    $wajibIds = \App\Models\User::whereIn('role', $wajibRoles)->pluck('id');

    // 2) HADIR/TELAT HARI INI (distinct per user) — hanya wajib
    $hadirUserIds = \App\Models\Presensi::whereDate('tanggal', $today)
        ->where('status','hadir')
        ->whereIn('user_id',$wajibIds)
        ->distinct()->pluck('user_id');

    $telatUserIds = \App\Models\Presensi::whereDate('tanggal', $today)
        ->where('status','telat')
        ->whereIn('user_id',$wajibIds)
        ->distinct()->pluck('user_id');

    $hadir = $hadirUserIds->count();
    $telat = $telatUserIds->count();

    // 3) IZIN/SAKIT HARI INI (approved; overlap today) — hanya wajib
    $izinUserIds = \App\Models\Izin::where('status','approved')
        ->where('jenis','izin')
        ->whereDate('tgl_mulai','<=',$today)
        ->whereDate('tgl_selesai','>=',$today)
        ->whereIn('user_id',$wajibIds)
        ->distinct()->pluck('user_id');

    $sakitUserIds = \App\Models\Izin::where('status','approved')
        ->where('jenis','sakit')
        ->whereDate('tgl_mulai','<=',$today)
        ->whereDate('tgl_selesai','>=',$today)
        ->whereIn('user_id',$wajibIds)
        ->distinct()->pluck('user_id');

    $izin  = $izinUserIds->count();
    $sakit = $sakitUserIds->count();

    // 4) BELUM ABSEN = total wajib - union(hadir,telat,izin,sakit)
    $sudahTercover = $hadirUserIds->merge($telatUserIds)->merge($izinUserIds)->merge($sakitUserIds)->unique();
    $totalWajib    = $wajibIds->count();
    $belum         = max($totalWajib - $sudahTercover->count(), 0);

    // 5) BARIS “HARI INI” (opsional tampil lintas role)
    $pegawai = \App\Models\User::whereIn('role', $rolesDipantau)
        ->orderBy('name')
        ->get(['id','name','jabatan','role']);

    $presensiMap = \App\Models\Presensi::with('user:id,name,role')
        ->whereDate('tanggal', $today)
        ->get()
        ->keyBy('user_id');

    $izinSet  = $izinUserIds->flip();
    $sakitSet = $sakitUserIds->flip();

    $todayRows = $pegawai->map(function ($u) use ($presensiMap, $izinSet, $sakitSet, $wajibRoles) {
        $p = $presensiMap->get($u->id);
        if ($p) {
            $status = $p->status ?? 'hadir';
        } else {
            // tanpa presensi: cek izin/sakit approved jika user wajib
            if (in_array($u->role, $wajibRoles)) {
                if ($izinSet->has($u->id))      $status = 'izin';
                elseif ($sakitSet->has($u->id)) $status = 'sakit';
                else                             $status = 'belum';
            } else {
                // non-wajib: jangan tandai "belum"
                $status = '-'; // atau 'n/a'
            }
        }

        return (object) [
            'user'        => $u,
            'status'      => $status,
            'jam_masuk'   => $p->jam_masuk   ?? null,
            'jam_keluar'  => $p->jam_keluar  ?? null,
            'telat_menit' => $p->telat_menit ?? null,
        ];
    });

    // 6) LOG TERBARU HARI INI
    $recent = \App\Models\Presensi::with('user:id,name,role')
        ->whereDate('tanggal', $today)
        ->orderByDesc('updated_at')
        ->limit(8)
        ->get();

    // 7) PANEL KANAN (filter hanya wajib untuk “Belum Absen”)
    $belumList = $todayRows->filter(fn($r)=> in_array($r->user->role,$wajibRoles) && $r->status==='belum')
                           ->values()->take(8);
    $hadirList = $todayRows->where('status','hadir')->values()->take(8);
    $telatList = $todayRows->where('status','telat')->values()->take(8);

    // 8) FLAG TOMBOL TU (UX)
    $me          = Auth::user();
    $todayRecord = \App\Models\Presensi::where('user_id', $me->id)
                        ->where('tanggal', $today)
                        ->first();

    $mStart = (string) config('presensi.jam_masuk_start',  '07:00');
    $mEnd   = (string) config('presensi.jam_masuk_end',    '09:00');
    $kStart = (string) config('presensi.jam_keluar_start', '16:00');
    $kEnd   = (string) config('presensi.jam_keluar_end',   '18:00');

    $canMasuk  = empty($todayRecord?->jam_masuk) && ($nowH >= $mStart && $nowH <= $mEnd);
    $canKeluar = !empty($todayRecord?->jam_masuk) && empty($todayRecord?->jam_keluar) && ($nowH >= $kStart && $nowH <= $kEnd);

    // 9) Kirim ke view (pakai nama variabel lama agar Blade aman)
    return view('tu.dashboard', [
        'totalGuru'   => $totalWajib, // kartu pertama pakai total wajib
        'hadir'       => $hadir,
        'telat'       => $telat,
        'izin'        => $izin,
        'sakit'       => $sakit,
        'belum'       => $belum,

        'recent'      => $recent,
        'todayRows'   => $todayRows,
        'belumList'   => $belumList,
        'hadirList'   => $hadirList,
        'telatList'   => $telatList,

        'todayRecord' => $todayRecord,
        'canMasuk'    => $canMasuk,
        'canKeluar'   => $canKeluar,
        'mStart'      => $mStart,
        'mEnd'        => $mEnd,
        'kStart'      => $kStart,
        'kEnd'        => $kEnd,
    ]);
}
}
