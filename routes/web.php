<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\IzinController;

// TU
use App\Http\Controllers\Tu\TuDashboardController;
use App\Http\Controllers\Tu\TuPresensiController;
use App\Http\Controllers\Tu\TuSelfPresensiController;
use App\Http\Controllers\Tu\TuExportController;

// Piket
use App\Http\Controllers\Piket\PiketDashboardController;
use App\Http\Controllers\Piket\PiketCekController;
use App\Http\Controllers\Piket\PiketRekapController;
use App\Http\Controllers\Piket\PiketRiwayatController;

//admin
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\PresensiController as AdminPresensiController;
use App\Http\Controllers\Admin\AdminIzinController;     // <-- tambahkan
use App\Http\Controllers\Admin\AdminExportController;   // <-- tambahkan

/*
|--------------------------------------------------------------------------
| Root & Redirect by Role
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    return match (Auth::user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'tu'    => redirect()->route('tu.dashboard'),
        'piket' => redirect()->route('piket.dashboard'),
        'kepsek'=> redirect()->route('kepsek.dashboard'),
        'guru'  => redirect()->route('presensi.index'),
        default => redirect()->route('login'),
    };
})->name('home');

Route::get('/redirect-role', function () {
    $role = Auth::user()->role ?? null;
    return match ($role) {
        'admin' => redirect()->route('admin.dashboard'),
        'tu'    => redirect()->route('tu.dashboard'),
        'piket' => redirect()->route('piket.dashboard'),
        'kepsek'=> redirect()->route('kepsek.dashboard'),
        'guru'  => redirect()->route('presensi.index'),
        default => redirect()->route('login'),
    };
})->middleware('auth')->name('redirect.role');

/*
|--------------------------------------------------------------------------
| ADMIN (contoh placeholder)
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth','role:admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class,'index'])->name('dashboard');

    // Master Users
    Route::resource('/users', AdminUserController::class)
        ->parameters(['users' => 'user'])
        ->names([
            'index'   => 'users.index',
            'create'  => 'users.create',
            'store'   => 'users.store',
            'show'    => 'users.show',
            'edit'    => 'users.edit',
            'update'  => 'users.update',
            'destroy' => 'users.destroy',
        ]);

    // Presensi (opsional: index/edit/update saja)
    Route::get('/presensi',                 [AdminPresensiController::class,'index'])->name('presensi.index');
    Route::get('/presensi/{presensi}/edit', [AdminPresensiController::class,'edit'  ])->name('presensi.edit');
    Route::patch('/presensi/{presensi}',    [AdminPresensiController::class,'update'])->name('presensi.update');

    // Izin — inilah yang diminta layout: admin.izin.index
    Route::get('/izin', [AdminIzinController::class,'index'])->name('izin.index');

    // Laporan / Export (kalau dipakai di layout)
    Route::get('/export', [AdminExportController::class,'index'])->name('export.index');
});



/*
|--------------------------------------------------------------------------
| PRESENSI PEGAWAI (Guru, Piket, TU pakai UI yang sama)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','role:guru,piket,tu'])->group(function () {
    // landing pegawai
    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');

    // form konfirmasi (dengan peta)
    Route::get('/presensi/masuk',  [PresensiController::class, 'formMasuk'])->name('presensi.formMasuk');
    Route::get('/presensi/keluar', [PresensiController::class, 'formKeluar'])->name('presensi.formKeluar');

    // simpan
    Route::post('/presensi/masuk',  [PresensiController::class, 'storeMasuk'])->name('presensi.storeMasuk');
    Route::post('/presensi/keluar', [PresensiController::class, 'storeKeluar'])->name('presensi.storeKeluar');

    // riwayat pribadi
    Route::get('/presensi/riwayat', [PresensiController::class, 'riwayat'])->name('presensi.riwayat');

    // izin pribadi (UI pegawai)
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

    // Dashboard TU
    Route::get('/dashboard', [TuDashboardController::class,'index'])->name('dashboard');

    // Lihat Presensi Guru (list + filter)
    Route::get('/presensi', [TuPresensiController::class,'index'])->name('presensi.index');

    // Riwayat Presensi per guru / filter rentang
    Route::get('/riwayat', [TuPresensiController::class,'riwayat'])->name('riwayat');

    // Export
    Route::get('/export',        [TuExportController::class,'index'])->name('export.index');
    Route::get('/export/excel',  [TuExportController::class,'exportExcel'])->name('export.excel');
    Route::get('/export/pdf',    [TuExportController::class,'exportPdf'])->name('export.pdf');

    // TU Self-Presensi (absen dirinya)
    Route::get('/absensi',         [TuSelfPresensiController::class,'index'])->name('absensi.index');
    Route::get('/absensi/masuk',   [TuSelfPresensiController::class,'formMasuk'])->name('absensi.formMasuk');
    Route::post('/absensi/masuk',  [TuSelfPresensiController::class,'storeMasuk'])->name('absensi.storeMasuk');
    Route::get('/absensi/keluar',  [TuSelfPresensiController::class,'formKeluar'])->name('absensi.formKeluar');
    Route::post('/absensi/keluar', [TuSelfPresensiController::class,'storeKeluar'])->name('absensi.storeKeluar');

    // Izin TU pribadi
    Route::get('/absensi/izin',         [TuSelfPresensiController::class,'izinIndex'])->name('absensi.izinIndex');
    Route::get('/absensi/izin/create',  [TuSelfPresensiController::class,'izinCreate'])->name('absensi.izinCreate');
    Route::post('/absensi/izin',        [TuSelfPresensiController::class,'izinStore'])->name('absensi.izinStore');
    Route::get('/absensi/izin/{izin}',  [TuSelfPresensiController::class,'izinShow'])->name('absensi.izinShow');
});

/*
|--------------------------------------------------------------------------
| KEPSEK (placeholder)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','role:kepsek'])->group(function () {
    Route::get('/kepsek/dashboard', function () {
        return view('kepsek.dashboard');
    })->name('kepsek.dashboard');
});

/*
|--------------------------------------------------------------------------
| PIKET
|--------------------------------------------------------------------------
*/
Route::prefix('piket')->name('piket.')->middleware(['auth','role:piket'])->group(function () {
    // Dashboard Piket
    Route::get('/dashboard', [PiketDashboardController::class,'index'])->name('dashboard');

    // Ngecek Guru (status hari ini)
    Route::get('/cek', [PiketCekController::class,'index'])->name('cek');

    // Presensi Manual (oleh Piket) — create/store
    Route::get('/absen-manual',  [PiketCekController::class, 'create'])->name('absen.create');
    Route::post('/absen-manual', [PiketCekController::class, 'store'])->name('absen.store');

    // Rekap harian (pilih tanggal)
    Route::get('/rekap', [PiketRekapController::class,'index'])->name('rekap');

    // Riwayat presensi (filter guru & rentang tanggal/bulan/tahun)
    Route::get('/riwayat', [PiketRiwayatController::class,'index'])->name('riwayat');
});

/*
|--------------------------------------------------------------------------
| PROFILE (umum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| AUTH scaffolding
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
