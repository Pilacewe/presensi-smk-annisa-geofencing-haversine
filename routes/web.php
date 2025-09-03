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

// Admin
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\PresensiController as AdminPresensiController;
use App\Http\Controllers\Admin\AdminIzinController;
use App\Http\Controllers\Admin\AdminExportController;
use App\Http\Controllers\Admin\AdminGuruController;
use App\Http\Controllers\Admin\AdminTuController;
use App\Http\Controllers\Admin\AdminPiketController;
// ✅ IMPORT YANG KURANG (AKUN ADMIN)
use App\Http\Controllers\Admin\Account\AdminAccountController;

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
| ADMIN
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth','role:admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class,'index'])->name('dashboard');

    // Presensi
    Route::get   ('/presensi',                [AdminPresensiController::class,'index'])->name('presensi.index');
    Route::get   ('/presensi/{presensi}/edit',[AdminPresensiController::class,'edit'])->name('presensi.edit');
    Route::patch ('/presensi/{presensi}',     [AdminPresensiController::class,'update'])->name('presensi.update');

    // Izin (admin)
    Route::get   ('/izin',                [AdminIzinController::class, 'index'])->name('izin.index');
    Route::patch ('/izin/{izin}/approve', [AdminIzinController::class, 'approve'])->name('izin.approve');
    Route::patch ('/izin/{izin}/reject',  [AdminIzinController::class, 'reject'])->name('izin.reject');
    Route::delete('/izin/{izin}',         [AdminIzinController::class, 'destroy'])->name('izin.destroy');
    Route::get   ('/izin/{izin}/bukti',   [AdminIzinController::class, 'bukti'])->name('izin.bukti');

    // Guru
    Route::prefix('guru')->name('guru.')->group(function () {
        Route::get('/',               [AdminGuruController::class, 'index'])->name('index');
        Route::get('/create',         [AdminGuruController::class, 'create'])->name('create');
        Route::post('/',              [AdminGuruController::class, 'store'])->name('store');
        Route::get('/{user}',         [AdminGuruController::class, 'show'])->name('show');
        Route::get('/{user}/edit',    [AdminGuruController::class, 'edit'])->name('edit');
        Route::patch('/{user}',       [AdminGuruController::class, 'update'])->name('update');
        Route::delete('/{user}',      [AdminGuruController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/reset-password', [AdminGuruController::class, 'resetPassword'])->name('reset');
        Route::get('/export',  [AdminGuruController::class, 'export'])->name('export');
        Route::post('/import', [AdminGuruController::class, 'import'])->name('import');
    });

    // TU
    Route::prefix('tu')->name('tu.')->group(function () {
        Route::get   ('/',            [AdminTuController::class, 'index'])->name('index');
        Route::get   ('/create',      [AdminTuController::class, 'create'])->name('create');
        Route::post  ('/',            [AdminTuController::class, 'store'])->name('store');
        Route::get   ('/{user}',      [AdminTuController::class, 'show'])->name('show');
        Route::get   ('/{user}/edit', [AdminTuController::class, 'edit'])->name('edit');
        Route::patch ('/{user}',      [AdminTuController::class, 'update'])->name('update');
        Route::delete('/{user}',      [AdminTuController::class, 'destroy'])->name('destroy');
        Route::post  ('/{user}/reset-password', [AdminTuController::class, 'resetPassword'])->name('reset');
        Route::post ('/import', [AdminTuController::class, 'import'])->name('import');
        Route::get  ('/export', [AdminTuController::class, 'export'])->name('export');
    });

    // ✅ AKUN ADMIN
    Route::post('/account/sessions/end-others', [AdminAccountController::class, 'endOtherSessions'])
    ->name('account.sessions.endOthers');
    Route::prefix('account')->name('account.')->group(function () {
        Route::get   ('/',           [AdminAccountController::class, 'index'])->name('index');
        Route::patch ('/profile',    [AdminAccountController::class, 'updateProfile'])->name('profile.update');
        Route::post  ('/avatar',     [AdminAccountController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('/avatar',     [AdminAccountController::class, 'deleteAvatar'])->name('avatar.delete');
        Route::patch ('/password',   [AdminAccountController::class, 'updatePassword'])->name('password.update');
        Route::patch ('/settings',   [AdminAccountController::class, 'updateSettings'])->name('settings.update');
        
    });

    // Piket
    Route::get   ('/piket',                  [AdminPiketController::class,'index'])->name('piket.index');
    Route::get   ('/piket/create',           [AdminPiketController::class,'create'])->name('piket.create');
    Route::post  ('/piket',                  [AdminPiketController::class,'store'])->name('piket.store');
    Route::get   ('/piket/{user}/edit',      [AdminPiketController::class,'edit'])->name('piket.edit');
    Route::post  ('/piket/{user}/reset',     [AdminPiketController::class,'reset'])->name('piket.reset');
    Route::put   ('/piket/{user}',           [AdminPiketController::class,'update'])->name('piket.update');
    Route::delete('/piket/{user}',           [AdminPiketController::class,'destroy'])->name('piket.destroy');
    Route::post  ('/piket/roster',           [AdminPiketController::class,'rosterStore'])->name('piket.roster.store');
    Route::delete('/piket/roster/{roster}',  [AdminPiketController::class,'rosterDestroy'])->name('piket.roster.destroy');

    // Export
    Route::get('/export', [AdminExportController::class,'index'])->name('export.index');
});

/*
|--------------------------------------------------------------------------
| PRESENSI PEGAWAI (Guru, Piket, TU)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','role:guru,piket,tu'])->group(function () {
    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
    Route::get('/presensi/masuk',  [PresensiController::class, 'formMasuk'])->name('presensi.formMasuk');
    Route::get('/presensi/keluar', [PresensiController::class, 'formKeluar'])->name('presensi.formKeluar');
    Route::post('/presensi/masuk',  [PresensiController::class, 'storeMasuk'])->name('presensi.storeMasuk');
    Route::post('/presensi/keluar', [PresensiController::class, 'storeKeluar'])->name('presensi.storeKeluar');
    Route::get('/presensi/riwayat', [PresensiController::class, 'riwayat'])->name('presensi.riwayat');

    // Izin (pegawai)
    Route::get   ('/izin',           [IzinController::class,'index'])->name('izin.index');
    Route::get   ('/izin/create',    [IzinController::class,'create'])->name('izin.create');
    Route::post  ('/izin',           [IzinController::class,'store'])->name('izin.store');
    Route::get   ('/izin/{izin}',    [IzinController::class,'show'])->name('izin.show');
    Route::delete('/izin/{izin}',    [IzinController::class,'destroy'])->name('izin.destroy');
});

/*
|--------------------------------------------------------------------------
| TU
|--------------------------------------------------------------------------
*/
Route::prefix('tu')->name('tu.')->middleware(['auth','role:tu'])->group(function () {
    Route::get('/dashboard', [TuDashboardController::class,'index'])->name('dashboard');
    Route::get('/presensi',  [TuPresensiController::class,'index'])->name('presensi.index');
    Route::get('/riwayat',   [TuPresensiController::class,'riwayat'])->name('riwayat');
    Route::get('/export',        [TuExportController::class,'index'])->name('export.index');
    Route::get('/export/excel',  [TuExportController::class,'exportExcel'])->name('export.excel');
    Route::get('/export/pdf',    [TuExportController::class,'exportPdf'])->name('export.pdf');

    // TU Self-Presensi
    Route::get ('/absensi',         [TuSelfPresensiController::class,'index'])->name('absensi.index');
    Route::get ('/absensi/masuk',   [TuSelfPresensiController::class,'formMasuk'])->name('absensi.formMasuk');
    Route::post('/absensi/masuk',   [TuSelfPresensiController::class,'storeMasuk'])->name('absensi.storeMasuk');
    Route::get ('/absensi/keluar',  [TuSelfPresensiController::class,'formKeluar'])->name('absensi.formKeluar');
    Route::post('/absensi/keluar',  [TuSelfPresensiController::class,'storeKeluar'])->name('absensi.storeKeluar');

    // Izin TU pribadi
    Route::get ('/absensi/izin',         [TuSelfPresensiController::class,'izinIndex'])->name('absensi.izinIndex');
    Route::get ('/absensi/izin/create',  [TuSelfPresensiController::class,'izinCreate'])->name('absensi.izinCreate');
    Route::post('/absensi/izin',         [TuSelfPresensiController::class,'izinStore'])->name('absensi.izinStore');
    Route::get ('/absensi/izin/{izin}',  [TuSelfPresensiController::class,'izinShow'])->name('absensi.izinShow');
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
    Route::get('/dashboard', [PiketDashboardController::class,'index'])->name('dashboard');
    Route::post('/dashboard/start', [\App\Http\Controllers\Piket\PiketDashboardController::class,'startShift'])
        ->name('dashboard.start');

    Route::get('/cek',    [PiketCekController::class,'index'])->name('cek');
    Route::get('/rekap',  [PiketRekapController::class,'index'])->name('rekap');
    Route::get('/riwayat',[PiketRiwayatController::class,'index'])->name('riwayat');

    Route::get ('/absen-manual',  [PiketCekController::class, 'create'])->name('absen.create');
    Route::post('/absen-manual',  [PiketCekController::class, 'store'])->name('absen.store');
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

require __DIR__.'/auth.php';
