<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\IzinController;
use App\Http\Controllers\Tu\TuDashboardController;
use App\Http\Controllers\Tu\TuPresensiController;
use App\Http\Controllers\Tu\TuExportController;

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
            'kepsek'=> redirect()->route('kepsek.dashboard'),
            // guru & piket pakai UI presensi yang sama
            'guru', 'piket' => redirect()->route('presensi.index'),
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
    $user = Auth::user();
    return match ($user->role ?? null) {
        'admin' => redirect()->route('admin.dashboard'),
        'tu'    => redirect()->route('tu.dashboard'),
        'kepsek'=> redirect()->route('kepsek.dashboard'),
        'guru', 'piket' => redirect()->route('presensi.index'),
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

    // Absensi Manual (TU melakukan presensi “masuk/keluar” untuk guru)
    Route::get('/absen', [TuPresensiController::class,'create'])->name('absen.create');
    Route::post('/absen', [TuPresensiController::class,'store'])->name('absen.store');

    // Export
    Route::get('/export', [TuExportController::class,'index'])->name('export.index');
    Route::get('/export/excel', [TuExportController::class,'exportExcel'])->name('export.excel');
    Route::get('/export/pdf',   [TuExportController::class,'exportPdf'])->name('export.pdf');
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
