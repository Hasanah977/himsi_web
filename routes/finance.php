<?php

use App\Http\Controllers\Bendahara\FinanceController;
use App\Http\Controllers\Bendahara\FinanceExportController;
use App\Http\Controllers\Bendahara\FundingController;
use App\Http\Controllers\Bendahara\MemberDuesController;
use App\Http\Middleware\AdministrationAccess;
use App\Http\Middleware\HimsiAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', HimsiAccess::class, AdministrationAccess::class])->prefix('bendahara')->name('finance.')->group(function () {
    Route::get('/dashboard', [FinanceController::class, 'dashboard'])->name('dashboard');
    Route::get('/transaksi/{kind}', [FinanceController::class, 'entries'])->name('entries');
    Route::post('/transaksi/{kind}', [FinanceController::class, 'store'])->name('store');
    Route::post('/transaksi/{entry}/batalkan', [FinanceController::class, 'void'])->name('void');
    Route::get('/pengajuan', [FundingController::class, 'index'])->name('funding');
    Route::get('/pengajuan/{submission}', [FundingController::class, 'show'])->name('funding.show');
    Route::post('/pengajuan/{submission}/cairkan', [FundingController::class, 'pay'])->name('funding.pay');
    Route::post('/pengajuan/{submission}/kembalikan', [FundingController::class, 'refund'])->name('funding.refund');
    Route::get('/penagihan', [FinanceController::class, 'billing'])->name('billing');
    Route::post('/penagihan/pengingat-semua', [FinanceController::class, 'remindAll'])->middleware('throttle:3,1')->name('remind.all');
    Route::post('/penagihan/{user}', [FinanceController::class, 'remind'])->middleware('throttle:30,1')->name('remind');
    Route::get('/laporan', [FinanceController::class, 'report'])->name('report');
    Route::get('/monitoring', [FinanceController::class, 'monitoring'])->name('monitoring');
    Route::get('/log', [FinanceController::class, 'logs'])->name('logs');
});
Route::middleware(['auth', HimsiAccess::class])->group(function () {
    Route::get('/administrasi/keuangan', [FinanceController::class, 'report'])->name('finance.shared');
    Route::get('/administrasi/keuangan/bukti/{entry}', [FinanceController::class, 'receipt'])->name('finance.receipt');
});

Route::middleware(['auth', HimsiAccess::class])->prefix('anggota/iuran')->name('member.dues.')->group(function () {
    Route::get('/', [MemberDuesController::class, 'index'])->name('index');
    Route::get('/{bulan}', [MemberDuesController::class, 'pay'])->whereNumber('bulan')->name('bayar');
    Route::post('/{bulan}', [MemberDuesController::class, 'store'])->whereNumber('bulan')->name('store');
});
Route::middleware(['auth', HimsiAccess::class])->get('/administrasi/keuangan/ekspor/{format}', FinanceExportController::class)->name('finance.export');
