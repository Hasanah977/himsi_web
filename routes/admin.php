<?php

use App\Http\Controllers\AdminSecurityController;
use App\Http\Controllers\GovernanceControlController;
use App\Http\Controllers\GovernanceController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Middleware\HimsiAccess;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    HimsiAccess::class.':super_admin',
])
    ->prefix('super-admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/kontrol-kepengurusan', [GovernanceControlController::class, 'index'])->name('governance-control.index');
        Route::put('/kontrol-kepengurusan', [GovernanceControlController::class, 'update'])->name('governance-control.update');
        Route::get('/periode', [GovernanceController::class, 'index'])->name('governance.index');
        Route::post('/periode', [GovernanceController::class, 'store'])->name('governance.store');
        Route::post('/periode/{period}/akhiri', [GovernanceController::class, 'end'])->whereNumber('period')->name('governance.end');
        Route::prefix('keamanan')->name('security.')->group(function () {
            $controller = AdminSecurityController::class;
            Route::get('/', [$controller, 'index'])->name('index');
            Route::post('/maintenance', [$controller, 'maintenance'])->name('maintenance');
            Route::post('/bekukan', [$controller, 'freeze'])->name('freeze');
            Route::post('/buka-akses', [$controller, 'unfreeze'])->name('unfreeze');
            Route::post('/backup', [$controller, 'backup'])->middleware('throttle:3,1')->name('backup');
            Route::get('/backup/{backup}', [$controller, 'download'])->whereUuid('backup')->name('download');
            Route::get('/restore/{backup}', [$controller, 'reviewRestore'])->whereUuid('backup')->name('restore.review');
            Route::post('/restore/{backup}', [$controller, 'restore'])->whereUuid('backup')->middleware('throttle:3,1')->name('restore');
            Route::get('/aktivitas', [$controller, 'logs'])->name('logs');
        });
        Route::get('/', [
            SuperAdminController::class,
            'dashboard',
        ])->name('dashboard');

        Route::get('/konten/{section}', [
            SuperAdminController::class,
            'index',
        ])->name('content.index');

        Route::get('/konten/{section}/tambah', [
            SuperAdminController::class,
            'create',
        ])->name('content.create');

        Route::post('/konten/{section}', [
            SuperAdminController::class,
            'store',
        ])->name('content.store');

        Route::get('/konten/{section}/{content}/edit', [
            SuperAdminController::class,
            'edit',
        ])->name('content.edit');

        Route::put('/konten/{section}/{content}', [
            SuperAdminController::class,
            'update',
        ])->name('content.update');

        Route::delete('/konten/{section}/{content}', [
            SuperAdminController::class,
            'destroy',
        ])->name('content.destroy');

        Route::get('/akun', [
            SuperAdminController::class,
            'users',
        ])->name('users.index');

        Route::get('/akun/tambah', [
            SuperAdminController::class,
            'createUser',
        ])->name('users.create');

        Route::post('/akun', [
            SuperAdminController::class,
            'storeUser',
        ])->name('users.store');

        Route::get('/akun/{encryptedAccount}/edit', [
            SuperAdminController::class,
            'editUser',
        ])->name('users.edit');

        Route::put('/akun/{encryptedAccount}', [
            SuperAdminController::class,
            'updateUser',
        ])->name('users.update');

        Route::put('/akun/{encryptedAccount}/jabatan', [SuperAdminController::class, 'appointment'])->name('users.appointment');
        Route::put('/akun/{encryptedAccount}/status', [SuperAdminController::class, 'accountStatus'])->name('users.status');

        Route::delete('/akun/{encryptedAccount}', [
            SuperAdminController::class,
            'destroyUser',
        ])->name('users.destroy');

        Route::get('/modul/{role}/{department?}', [
            SuperAdminController::class,
            'module',
        ])->name('module');
    });
