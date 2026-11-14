<?php

use App\Http\Controllers\Administration\OfficerReportController;
use App\Http\Controllers\Bendahara\FundingController;
use App\Http\Controllers\Department\AnnouncementController;
use App\Http\Controllers\Department\DashboardController;
use App\Http\Controllers\Department\ForumController;
use App\Http\Controllers\Department\MemberController;
use App\Http\Controllers\Department\MemberNotificationController;
use App\Http\Controllers\Department\PprkController;
use App\Http\Controllers\Department\ProgramController;
use App\Http\Controllers\Department\ReviewController;
use App\Http\Controllers\Department\SubmissionController;
use App\Http\Controllers\Department\TaskController;
use App\Http\Middleware\DepartmentAccess;
use App\Http\Middleware\HimsiAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', HimsiAccess::class])->group(function () {
    Route::prefix('lpj')->name('officer-reports.')->controller(OfficerReportController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/template', 'template')->name('template');
        Route::get('/{report}', 'show')->name('show');
        Route::put('/{report}', 'update')->name('update');
        Route::post('/{report}/verifikasi', 'review')->name('review');
        Route::get('/{report}/berkas', 'file')->name('file');
    });
    Route::get('/operasional/pprk', [PprkController::class, 'index'])->name('department.pprk');
    Route::post('/operasional/pprk', [PprkController::class, 'upload'])->name('department.pprk.upload');
    Route::post('/operasional/pprk/{pprk}', [PprkController::class, 'review'])->name('department.pprk.review');
    Route::get('/operasional/pprk/{pprk}/berkas', [PprkController::class, 'file'])->name('department.pprk.file');
    Route::get('/operasional/tinjauan', [ReviewController::class, 'index'])->name('department.reviews');
    Route::get('/operasional/tinjauan/{submission}', [ReviewController::class, 'show'])->name('department.review');
    Route::post('/operasional/tinjauan/{submission}', [ReviewController::class, 'decide'])->name('department.review.save');
    Route::get('/operasional/berkas/{submission}', [SubmissionController::class, 'file'])->name('department.submission.file');
    Route::prefix('/departemen/{department}')->name('department.')->group(function () {
        Route::middleware(DepartmentAccess::class)->group(function () {
            Route::post('/rab/{submission}/pengembalian', [FundingController::class, 'departmentRefund'])->name('budget.refund');
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/program-kerja', [ProgramController::class, 'index'])->name('programs');
            Route::get('/program-kerja/tambah', [ProgramController::class, 'create'])->name('program.create');
            Route::post('/program-kerja', [ProgramController::class, 'store'])->name('program.store');
            Route::get('/program-kerja/{program}', [ProgramController::class, 'show'])->name('program.show');
            Route::get('/program-kerja/{program}/edit', [ProgramController::class, 'edit'])->name('program.edit');
            Route::delete('/program-kerja/{program}', [ProgramController::class, 'destroy'])->name('program.destroy');
            Route::put('/program-kerja/{program}', [ProgramController::class, 'update'])->name('program.update');
            Route::post('/program-kerja/{program}/status', [ProgramController::class, 'transition'])->name('program.transition');
            Route::get('/pengajuan/{type}', [SubmissionController::class, 'index'])->name('submissions');
            Route::get('/program-kerja/{program}/pengajuan/{type}', [SubmissionController::class, 'edit'])->name('submission.edit');
            Route::post('/program-kerja/{program}/pengajuan/{type}', [SubmissionController::class, 'save'])->name('submission.save');
            Route::get('/template/{type}', [SubmissionController::class, 'documentTemplate'])->name('document.template');
            Route::get('/template-lpj', [SubmissionController::class, 'template'])->name('report.template');
            Route::get('/distribusi-tugas', [TaskController::class, 'index'])->name('tasks');
            Route::get('/distribusi-tugas/tambah', [TaskController::class, 'create'])->name('task.create');
            Route::post('/distribusi-tugas', [TaskController::class, 'store'])->name('task.store');
            Route::get('/monitoring', [TaskController::class, 'index'])->name('monitoring');
            Route::post('/monitoring/{task}', [TaskController::class, 'review'])->name('task.review');
            Route::post('/forum/grup', [ForumController::class, 'create'])->name('forum.create');
        });
        Route::middleware(DepartmentAccess::class.':member')->group(function () {
            Route::get('/anggota', [MemberController::class, 'dashboard'])->name('member.dashboard');
            Route::get('/anggota/program-kerja', [MemberController::class, 'programs'])->name('member.programs');
            Route::get('/anggota/program-kerja/{program}', [MemberController::class, 'program'])->whereNumber('program')->name('member.program');
            Route::get('/anggota/pengumuman', [AnnouncementController::class, 'index'])->name('member.announcements');
            Route::get('/anggota/pengumuman/{content}', [AnnouncementController::class, 'show'])->whereNumber('content')->name('member.announcement');
            Route::get('/anggota/notifikasi', [MemberNotificationController::class, 'index'])->name('member.notifications');
            Route::post('/anggota/notifikasi/baca-semua', [MemberNotificationController::class, 'readAll'])->name('member.notifications.read');
            Route::delete('/anggota/notifikasi', [MemberNotificationController::class, 'destroy'])->name('member.notifications.destroy');
            Route::get('/tugas-saya', [TaskController::class, 'index'])->name('my-tasks');
            Route::post('/tugas-saya/{task}', [TaskController::class, 'submit'])->name('task.submit');
            Route::get('/tugas/{task}/berkas', [TaskController::class, 'file'])->name('task.file');
        });
        Route::middleware(DepartmentAccess::class.':forum')->group(function () {
            Route::put('/forum/pesan/{message}', [ForumController::class, 'update'])->name('forum.update');
            Route::delete('/forum/pesan/{message}', [ForumController::class, 'destroy'])->name('forum.destroy');
            Route::get('/forum', [ForumController::class, 'index'])->name('forum');
            Route::post('/forum/{group}/pesan', [ForumController::class, 'send'])->middleware('throttle:30,1')->name('forum.send');
            Route::get('/forum/{group}/pembaruan', [ForumController::class, 'updates'])->name('forum.updates');
            Route::get('/forum/berkas/{message}', [ForumController::class, 'file'])->name('forum.file');
        });
    });
});
