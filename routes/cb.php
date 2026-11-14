<?php

use App\Http\Controllers\Cb\ActivityController;
use App\Http\Controllers\Cb\DashboardController;
use App\Http\Controllers\Cb\ManualRegistrationController;
use App\Http\Controllers\Cb\ParticipantController;
use App\Http\Controllers\Cb\RecruitmentController;
use App\Http\Middleware\HimsiAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', HimsiAccess::class.':panitia_cb'])->prefix('cb')->name('cb.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/rekrutmen', [RecruitmentController::class, 'index'])->name('recruitment');
    Route::get('/rekrutmen/tambah', [ManualRegistrationController::class, 'create'])->name('recruitment.create');
    Route::post('/rekrutmen', [ManualRegistrationController::class, 'store'])->name('recruitment.store');
    Route::get('/rekrutmen/{registration}', [RecruitmentController::class, 'show'])->name('recruitment.show');
    Route::post('/rekrutmen/{registration}/review', [RecruitmentController::class, 'review'])->name('recruitment.review');
    Route::post('/rekrutmen/{registration}/berkas', [RecruitmentController::class, 'replace'])->name('recruitment.replace');
    Route::get('/rekrutmen/{registration}/berkas/{document}', [RecruitmentController::class, 'document'])->name('recruitment.document');
    Route::get('/peserta', [ParticipantController::class, 'index'])->name('participants');
    Route::get('/peserta/tambah', [ParticipantController::class, 'create'])->name('participants.create');
    Route::post('/peserta', [ParticipantController::class, 'store'])->name('participants.store');
    Route::get('/peserta/{participant}/edit', [ParticipantController::class, 'edit'])->name('participants.edit');
    Route::put('/peserta/{participant}', [ParticipantController::class, 'update'])->name('participants.update');
    Route::post('/peserta/{participant}/seleksi', [ParticipantController::class, 'select'])->name('participants.select');
    Route::get('/kegiatan', [ActivityController::class, 'index'])->name('activities');
    Route::get('/kegiatan/tambah', [ActivityController::class, 'create'])->name('activities.create');
    Route::post('/kegiatan', [ActivityController::class, 'store'])->name('activities.store');
    Route::get('/kegiatan/{activity}', [ActivityController::class, 'show'])->name('activities.show');
    Route::get('/kegiatan/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
    Route::put('/kegiatan/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::get('/kegiatan/{activity}/laporan', [ActivityController::class, 'report'])->name('activities.report');
    Route::post('/kegiatan/{activity}/peserta', [ActivityController::class, 'enroll'])->name('activities.enroll');
    Route::post('/kegiatan/{activity}/absen/{attendance}', [ActivityController::class, 'attendance'])->name('activities.attendance');
    Route::post('/kegiatan/{activity}/dokumentasi', [ActivityController::class, 'upload'])->name('activities.upload');
    Route::get('/kegiatan/{activity}/dokumentasi/{attachment}', [ActivityController::class, 'file'])->name('activities.file');
    Route::delete('/kegiatan/{activity}/dokumentasi/{attachment}', [ActivityController::class, 'deleteFile'])->name('activities.delete-file');
});
