<?php

use App\Http\Controllers\HimsiRegistrationController;
use App\Http\Controllers\kbh\ForumController;
use App\Http\Controllers\MemberArchiveController;
use App\Http\Controllers\NotifikasiHimsiController;
use App\Http\Controllers\RegistrationReviewController;
use App\Http\Middleware\HimsiAccess;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/daftar', [HimsiRegistrationController::class, 'create'])->name('registration.create');
    Route::post('/daftar', [HimsiRegistrationController::class, 'store'])
        ->middleware('throttle:5,1')->name('registration.store');
    Route::get('/pendaftaran/terkirim', [HimsiRegistrationController::class, 'success'])->name('registration.success');
});

Route::middleware('auth')->group(function () {
    Route::get('/pendaftaran/pemeriksaan', [RegistrationReviewController::class, 'index'])->name('registration.review');
    Route::post('/pendaftaran/pemeriksaan', [RegistrationReviewController::class, 'update'])->middleware('throttle:10,1')->name('registration.revise');
    Route::get('/pendaftaran/berkas/{document}', [RegistrationReviewController::class, 'document'])->name('registration.document');
});

Route::middleware(['auth', HimsiAccess::class])->group(function () {
    Route::delete('/notifikasi-himsi/semua', [NotifikasiHimsiController::class, 'destroyAll'])->name('notifikasi-himsi.destroy-all');
    Route::get('/arsip-pribadi', [MemberArchiveController::class, 'index'])->name('member.archive');
    Route::post('/arsip-pribadi', [MemberArchiveController::class, 'store'])->middleware('throttle:20,1')->name('member.archive.store');
    Route::get('/arsip-pribadi/{source}/{record}/{field}', [MemberArchiveController::class, 'file'])->whereNumber('record')->name('member.archive.file');
    Route::put('/arsip-pribadi/{document}', [MemberArchiveController::class, 'update'])->whereNumber('document')->name('member.archive.update');
    Route::delete('/arsip-pribadi/{document}', [MemberArchiveController::class, 'destroy'])->whereNumber('document')->name('member.archive.destroy');
    Route::get('/kbh/forum/berkas/{message}', [ForumController::class, 'file'])->name('kbh.forum.file');
    Route::put('/kbh/forum/pesan/{message}', [ForumController::class, 'update'])->name('kbh.forum.update');
    Route::delete('/kbh/forum/pesan/{message}', [ForumController::class, 'destroy'])->name('kbh.forum.destroy');
    Route::get('/kbh/forum', [ForumController::class, 'index'])->name('kbh.forum');
    Route::post('/kbh/forum/grup', [ForumController::class, 'store'])->name('kbh.forum.store');
    Route::post('/kbh/forum/{group}/pesan', [ForumController::class, 'send'])->middleware('throttle:30,1')->name('kbh.forum.send');
});
