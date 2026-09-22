<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TiketController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberGateController;
use App\Http\Controllers\ForgotPasswordController; // <-- 1. IMPORT CONTROLLER INI

// ===================================
// PUBLIC ROUTES (BEBAS AKSES TANPA TOKEN)
// ===================================
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/member/check', [MemberController::class, 'check']);
Route::post('/tiket', [TiketController::class, 'create']);
Route::get('/tiket/{kode}', [TiketController::class, 'showByKode']);
Route::get('/qrcode/{kode}', [TiketController::class, 'qrcode']);

// Route Lupa Password Petugas (Fonnte OTP) - WAJIB DI PUBLIC
Route::post('/forgot-password/check-email', [ForgotPasswordController::class, 'checkEmail']);
Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/resend-otp', [ForgotPasswordController::class, 'resendOtp']);
Route::post('/forgot-password/verify-reset', [ForgotPasswordController::class, 'verifyAndReset']);


// ===================================
// PROTECTED ROUTES (WAJIB LOGIN / TOKEN SANCTUM)
// ===================================
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Route Petugas & Admin yang diizinkan operasi kasir / parkir
    Route::middleware(['role:petugas,admin,super_admin'])->group(function () {
        Route::post('/gate/masuk-member', [MemberGateController::class, 'masukMember']);
        Route::post('/gate/scan', [MemberGateController::class, 'scanGate']);
        Route::get('/transaksi', [TransaksiController::class, 'index']);
        Route::post('/scan', [PaymentController::class, 'scan']);
        Route::post('/payment', [PaymentController::class, 'bayar']);
        Route::post('/transaksi/bayar', [TransaksiController::class, 'store']);
        Route::get('/dashboard/stats', [DashboardController::class, 'index']);
        Route::get('/parkir/aktif', [TiketController::class, 'kendaraanAktif']);
        Route::get('/laporan/member', [LaporanController::class, 'member']);
        Route::get('/laporan/non-member', [LaporanController::class, 'nonMember']);
    });

    // Route Member (petugas/admin/super admin)
    Route::middleware(['role:petugas,admin,super_admin'])->group(function () {
        Route::post('/member/bayar/{id}', [MemberController::class, 'bayarMember']);
        Route::put('/member/{id}/pembayaran', [MemberController::class, 'updatePembayaran']);
        Route::apiResource('/member', MemberController::class);
    });

    // Route Admin Petugas (admin & super admin)
    Route::middleware(['role:admin,super_admin'])->group(function () {
        Route::get('/admin/petugas', [UserController::class, 'index']);
        Route::post('/admin/petugas', [UserController::class, 'store']);
        Route::put('/admin/petugas/{id}', [UserController::class, 'update']);
        Route::put('/admin/petugas/{id}/reset-password', [UserController::class, 'updatePassword']);
        Route::delete('/admin/petugas/{id}', [UserController::class, 'destroy']);
        Route::get('/admin/laporan', [LaporanController::class, 'rekapGlobal']);
    });

    // Route Simulasi (admin & super admin)
    Route::middleware(['role:admin,super_admin'])->group(function () {
        Route::get('/simulasi/status', [\App\Http\Controllers\SimulasiController::class, 'status']);
        Route::post('/simulasi/advance-month', [\App\Http\Controllers\SimulasiController::class, 'advanceMonth']);
        Route::post('/simulasi/rewind-month', [\App\Http\Controllers\SimulasiController::class, 'rewindMonth']);
        Route::post('/simulasi/reset', [\App\Http\Controllers\SimulasiController::class, 'reset']);
    });

});