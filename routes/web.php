<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\IzinController;
use App\Http\Controllers\Tu\TuDashboardController;
use App\Http\Controllers\Tu\TuPresensiController;
use App\Http\Controllers\Tu\TuSelfPresensiController;
use App\Http\Controllers\Tu\TuExportController;
use App\Http\Controllers\Piket\PiketDashboardController;
use App\Http\Controllers\Piket\PiketCekController;
use App\Http\Controllers\Piket\PiketRekapController;
use App\Http\Controllers\Piket\PiketRiwayatController;
/*
|--------------------------------------------------------------------------
| Halaman awal
|--------------------------------------------------------------------------
| Tamu -> login. User login -> redirect by role.
*/
Route::get('/', function () {
    if (Auth::check()) {
        return match (Auth::user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'tu'    => redirect()->route('tu.dashboard'),
            'piket' => redirect()->route('piket.dashboard'),   // <-- ubah ke dashboard piket
            'kepsek'=> redirect()->route('kepsek.dashboard'),
            'guru'  => redirect()->route('presensi.index'),
            default => redirect()->route('login'),
        };
    }
    return redirect()->route('login');
})->name('home');

/*
|--------------------------------------------------------------------------
| Redirect setelah login (opsional)
|--------------------------------------------------------------------------
| Jika kamu panggil ini dari AuthenticatedSessionController.
*/
Route::get('/redirect-role', function () {
    $r = Auth::user()->role ?? null;
    return match ($r) {
        'admin' => redirect()->route('admin.dashboard'),
        'tu'    => redirect()->route('tu.dashboard'),
        'piket' => redirect()->route('piket.dashboard'),       // <-- ubah ke dashboard piket
        'kepsek'=> redirect()->route('kepsek.dashboard'),
        'guru'  => redirect()->route('presensi.index'),
        default => redirect()->route('login'),
    };
})->middleware('auth')->name('redirect.role');

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});

/*
|--------------------------------------------------------------------------
| PRESENSI PEGAWAI (Guru & Piket memakai UI yang sama)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:guru,piket,tu'])->group(function () {
    // landing pegawai
    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');

    // halaman konfirmasi + peta
    Route::get('/presensi/masuk',  [PresensiController::class, 'formMasuk'])->name('presensi.formMasuk');
    Route::get('/presensi/keluar', [PresensiController::class, 'formKeluar'])->name('presensi.formKeluar');

    // aksi simpan
    Route::post('/presensi/masuk',  [PresensiController::class, 'storeMasuk'])->name('presensi.storeMasuk');
    Route::post('/presensi/keluar', [PresensiController::class, 'storeKeluar'])->name('presensi.storeKeluar');

    // riwayat presensi pribadi
    Route::get('/presensi/riwayat', [PresensiController::class, 'riwayat'])->name('presensi.riwayat');

    // izin (buat/lihat/batal) — hanya untuk yang memakai UI pegawai
    Route::get('/izin',            [IzinController::class,'index'])->name('izin.index');
    Route::get('/izin/create',     [IzinController::class,'create'])->name('izin.create');
    Route::post('/izin',           [IzinController::class,'store'])->name('izin.store');
    Route::get('/izin/{izin}',     [IzinController::class,'show'])->name('izin.show');
    Route::delete('/izin/{izin}',  [IzinController::class,'destroy'])->name('izin.destroy');
});

/*
|--------------------------------------------------------------------------
| TU (Tata Usaha)
|--------------------------------------------------------------------------
*/
Route::prefix('tu')->name('tu.')->middleware(['auth','role:tu'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [TuDashboardController::class,'index'])->name('dashboard');

    // Lihat Presensi Guru (list + filter)
    Route::get('/presensi', [TuPresensiController::class,'index'])->name('presensi.index');

    // Riwayat Presensi per guru / filter rentang
    Route::get('/riwayat', [TuPresensiController::class,'riwayat'])->name('riwayat');

    // Export
    Route::get('/export', [TuExportController::class,'index'])->name('export.index');
    Route::get('/export/excel', [TuExportController::class,'exportExcel'])->name('export.excel');
    Route::get('/export/pdf',   [TuExportController::class,'exportPdf'])->name('export.pdf');
     // TU Self-Presensi (khusus TU absen dirinya)
    Route::get('/absensi',               [\App\Http\Controllers\Tu\TuSelfPresensiController::class,'index'])->name('absensi.index');
    Route::get('/absensi/masuk',         [\App\Http\Controllers\Tu\TuSelfPresensiController::class,'formMasuk'])->name('absensi.formMasuk');
    Route::post('/absensi/masuk',        [\App\Http\Controllers\Tu\TuSelfPresensiController::class,'storeMasuk'])->name('absensi.storeMasuk');
    Route::get('/absensi/keluar',        [\App\Http\Controllers\Tu\TuSelfPresensiController::class,'formKeluar'])->name('absensi.formKeluar');
    Route::post('/absensi/keluar',       [\App\Http\Controllers\Tu\TuSelfPresensiController::class,'storeKeluar'])->name('absensi.storeKeluar');

    // TU – Izin pribadi
    Route::get('/absensi/izin',          [\App\Http\Controllers\Tu\TuSelfPresensiController::class,'izinIndex'])->name('absensi.izinIndex');
    Route::get('/absensi/izin/create',   [\App\Http\Controllers\Tu\TuSelfPresensiController::class,'izinCreate'])->name('absensi.izinCreate');
    Route::post('/absensi/izin',         [\App\Http\Controllers\Tu\TuSelfPresensiController::class,'izinStore'])->name('absensi.izinStore');
    Route::get('/absensi/izin/{izin}',   [\App\Http\Controllers\Tu\TuSelfPresensiController::class,'izinShow'])->name('absensi.izinShow');
});

/*
|--------------------------------------------------------------------------
| KEPSEK
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:kepsek'])->group(function () {
    Route::get('/kepsek/dashboard', function () {
        return view('kepsek.dashboard');
    })->name('kepsek.dashboard');
});

Route::prefix('piket')->name('piket.')->middleware(['auth','role:piket'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [PiketDashboardController::class,'index'])->name('dashboard');

    // Ngecek Guru (status hari ini)
    Route::get('/cek', [PiketCekController::class,'index'])->name('cek');

    // Absensi Manual (TU melakukan presensi “masuk/keluar” untuk guru)
    Route::get('/absen', [PiketCekController::class,'create'])->name('absen.create');
    Route::post('/absen', [PiketCekController::class,'store'])->name('absen.store');

    // Rekap harian (pilih tanggal)
    Route::get('/rekap', [PiketRekapController::class,'index'])->name('rekap');

    // Riwayat presensi (filter guru + rentang tanggal)
    Route::get('/riwayat', [PiketRiwayatController::class,'index'])->name('riwayat');
});
/*
|--------------------------------------------------------------------------
| PROFILE (umum, hanya butuh auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| AUTH scaffolding
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
