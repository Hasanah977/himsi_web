<?php

use App\Http\Controllers\Kpum\CandidateController;
use App\Http\Controllers\Kpum\ElectionController;
use App\Http\Controllers\Kpum\RegistrationController;
use App\Http\Controllers\Kpum\VoteController;
use App\Http\Middleware\HimsiAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', HimsiAccess::class.':panitia_kpum'])->prefix('kpum')->name('kpum.')->group(function () {
    Route::get('/', [ElectionController::class, 'dashboard'])->name('dashboard');
    Route::get('/kandidat', [ElectionController::class, 'candidates'])->name('candidates');
    Route::post('/pendaftaran', [RegistrationController::class, 'toggle'])->name('registration.toggle');
    Route::get('/kandidat/tambah', [CandidateController::class, 'create'])->name('candidates.create');
    Route::post('/kandidat', [CandidateController::class, 'store'])->name('candidates.store');
    Route::get('/kandidat/{candidate}/edit', [CandidateController::class, 'edit'])->name('candidates.edit');
    Route::put('/kandidat/{candidate}', [CandidateController::class, 'update'])->name('candidates.update');
    Route::post('/kandidat/{candidate}/verifikasi', [CandidateController::class, 'review'])->name('candidates.review');
    Route::delete('/kandidat/{candidate}', [CandidateController::class, 'destroy'])->name('candidates.destroy');
    Route::get('/kandidat/{candidate}/berkas/{document}', [CandidateController::class, 'document'])->name('candidates.document');
    Route::get('/kontrol', [ElectionController::class, 'control'])->name('control');
    Route::get('/pemilih', [ElectionController::class, 'voters'])->name('voters');
    Route::post('/pemilih/pilih-semua', [ElectionController::class, 'allVoters'])->name('voters.all');
    Route::post('/pemilih/{user}', [ElectionController::class, 'voter'])->name('voters.update');
    Route::put('/jadwal', [ElectionController::class, 'settings'])->name('settings');
    Route::post('/buka', [ElectionController::class, 'open'])->name('open');
    Route::post('/tutup', [ElectionController::class, 'close'])->name('close');
    Route::get('/hasil', [ElectionController::class, 'results'])->name('results');
    Route::post('/sahkan', [ElectionController::class, 'certify'])->name('certify');
    Route::get('/pengumuman', [ElectionController::class, 'announcement'])->name('announcement');
    Route::post('/pengumuman', [ElectionController::class, 'publish'])->name('publish');
    Route::get('/statistik', [ElectionController::class, 'stats'])->name('stats');
    Route::get('/rekap.csv', [ElectionController::class, 'export'])->name('export');
});

Route::middleware(['auth', HimsiAccess::class])->group(function () {
    Route::get('/kbh/seleksi/ketua-umum', [RegistrationController::class, 'create'])->name('kpum.registration.create');
    Route::post('/kbh/seleksi/ketua-umum', [RegistrationController::class, 'store'])->middleware('throttle:10,1')->name('kpum.registration.store');
    Route::get('/pemilihan-kpum', [VoteController::class, 'index'])->name('kpum.vote');
    Route::post('/pemilihan-kpum', [VoteController::class, 'store'])->middleware('throttle:10,1')->name('kpum.vote.submit');
    Route::get('/kpum-foto/{candidate}', [CandidateController::class, 'photo'])->name('kpum.photo');
});
