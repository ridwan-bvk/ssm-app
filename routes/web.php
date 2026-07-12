<?php

use App\Http\Controllers\QrPrintController;
use App\Http\Controllers\TeacherQrPrintController;
use Illuminate\Support\Facades\Route;

/**
 * Mirrors the role-based redirect closure in the CI4 app's
 * app/Config/Routes.php: superadmin/admin/kepsek all land on the Filament
 * admin panel, guru-only users go to the Teacher panel (built later in
 * Phase 1), guests are sent to the login screen.
 */
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect('/admin/login');
    }

    $user = auth()->user();

    if ($user->hasRole('guru') && ! $user->hasAnyRole(['superadmin', 'admin', 'kepsek'])) {
        return redirect('/teacher');
    }

    return redirect('/admin');
});

Route::middleware(['auth', 'can:qr.generate'])->prefix('qr')->name('qr.')->group(function () {
    Route::get('/print/siswa', [QrPrintController::class, 'siswa'])->name('print.siswa');
    Route::get('/print/siswa/{siswa}', [QrPrintController::class, 'siswaSingle'])->name('print.siswa.single');
    Route::get('/print/guru', [QrPrintController::class, 'guru'])->name('print.guru');
    Route::get('/print/guru/{guru}', [QrPrintController::class, 'guruSingle'])->name('print.guru.single');
});

Route::middleware(['auth', 'can:teacher.access'])->prefix('teacher/qr')->name('teacher.qr.')->group(function () {
    Route::get('/print/siswa', [TeacherQrPrintController::class, 'siswa'])->name('print.siswa');
    Route::get('/print/siswa/{siswa}', [TeacherQrPrintController::class, 'siswaSingle'])->name('print.siswa.single');
});

/*
|--------------------------------------------------------------------------
| Phase 2: Vue PWA shell routes
|--------------------------------------------------------------------------
| Mirrors the CI4 app's scan/izin/cek-kehadiran routes: /scan required a
| logged-in session there (any role — no specific permission beyond that),
| /izin and /cek-kehadiran were the only fully public routes.
*/
Route::middleware('auth')->get('/scan', fn () => view('pwa'));
Route::get('/izin', fn () => view('pwa'));
Route::get('/cek-kehadiran', fn () => view('pwa'));
