<?php

use App\Http\Controllers\Administration\RecruitmentController;
use App\Http\Controllers\GovernanceController;
use App\Http\Controllers\HimsiAuthController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\RoleWelcomeController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Middleware\HimsiAccess;
use App\Models\PprkSubmission;
use App\Models\PublicContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
|--------------------------------------------------------------------------
| HALAMAN PUBLIK
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('himsi', [
        'page' => 'home',
        'title' => 'Beranda',
        'statistics' => [
            'members_count' => User::query()->where('is_active', true)
                ->whereIn('role', ['kbh', ...User::OFFICER_ROLES])
                ->whereDoesntHave('registration', fn (Builder $query) => $query->where('status', '!=', 'approved'))->count(),
            'programs_count' => PublicContent::query()->where('section', 'programs')
                ->where('is_published', true)->whereNull('department')->where('payload->status', 'Sedang Berjalan')->count(),
            'achievements_count' => PublicContent::query()->where('section', 'achievements')
                ->where('is_published', true)->whereNull('department')->count(),
        ],
    ]);
})->name('home');

Route::get('/storage/himsi/{filename}', function (string $filename): StreamedResponse {
    $path = 'himsi/'.$filename;
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path, headers: ['X-Content-Type-Options' => 'nosniff']);
})->where('filename', '[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp|gif)')->name('public.image');

Route::get('/profil', function () {
    return view('himsi', [
        'page' => 'profile',
        'title' => 'Profil Himpunan',
    ]);
})->name('profile');

Route::get('/struktur', function () {
    return view('himsi', [
        'page' => 'structure',
        'title' => 'Struktur Organisasi',
    ]);
})->name('structure');

/*
|--------------------------------------------------------------------------
| DAFTAR PROGRAM KERJA
|--------------------------------------------------------------------------
*/

Route::get('/program-kerja', function (Request $request) {
    $request->validate([
        'q' => ['nullable', 'string', 'max:120'],
        'filter' => ['nullable', 'string', 'max:80'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);

    $query = mb_strtolower(
        trim((string) $request->query('q', ''))
    );

    $filter = $request->query('filter') ?: 'Semua';

    $items = collect(PublicContent::items('programs'))
        ->filter(function ($item) use ($query, $filter) {
            $matchesFilter = $filter === 'Semua'
                || $item['status'] === $filter;

            $text = mb_strtolower(
                $item['title'].' '.
                $item['summary'].' '.$item['category']
            );

            $matchesQuery = $query === ''
                || str_contains($text, $query);

            return $matchesFilter && $matchesQuery;
        })
        ->values();

    return view('himsi', [
        'page' => 'programs',
        'title' => 'Program Kerja',
        'items' => (new LengthAwarePaginator($items->forPage($request->integer('page', 1), 6)->values(), $items->count(), 6, $request->integer('page', 1), ['path' => $request->url()]))->withQueryString(),
    ]);
})->name('programs');

/*
|--------------------------------------------------------------------------
| DETAIL PROGRAM KERJA
|--------------------------------------------------------------------------
*/

Route::get('/program-kerja/{slug}', function (string $slug) {
    $item = collect(PublicContent::items('programs'))
        ->firstWhere('slug', $slug);

    abort_unless($item, 404);

    return view('himsi', [
        'page' => 'detail',
        'title' => $item['title'],
        'item' => $item,
        'backRoute' => 'programs',
        'backLabel' => 'Program Kerja',
    ]);
})->name('program.detail');

/*
|--------------------------------------------------------------------------
| DAFTAR PENGUMUMAN
|--------------------------------------------------------------------------
*/

Route::get('/pengumuman', function (Request $request) {
    $request->validate([
        'q' => ['nullable', 'string', 'max:120'],
        'filter' => ['nullable', 'string', 'max:80'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);

    $query = mb_strtolower(
        trim((string) $request->query('q', ''))
    );

    $filter = $request->query('filter') ?: 'Semua';

    $items = collect(PublicContent::items('announcements'))
        ->filter(function ($item) use ($query, $filter) {
            $matchesFilter = $filter === 'Semua'
                || $item['category'] === $filter;

            $text = mb_strtolower(
                $item['title'].' '.$item['summary']
            );

            $matchesQuery = $query === ''
                || str_contains($text, $query);

            return $matchesFilter && $matchesQuery;
        })
        ->values();

    return view('himsi', [
        'page' => 'announcements',
        'title' => 'Pengumuman',
        'items' => (new LengthAwarePaginator($items->forPage($request->integer('page', 1), 6)->values(), $items->count(), 6, $request->integer('page', 1), ['path' => $request->url()]))->withQueryString(),
    ]);
})->name('announcements');

/*
|--------------------------------------------------------------------------
| DETAIL PENGUMUMAN
|--------------------------------------------------------------------------
*/

Route::get('/pengumuman/{slug}', function (string $slug) {
    $item = collect(PublicContent::items('announcements'))
        ->firstWhere('slug', $slug);

    abort_unless($item, 404);

    return view('himsi', [
        'page' => 'detail',
        'title' => $item['title'],
        'item' => $item,
        'backRoute' => 'announcements',
        'backLabel' => 'Pengumuman',
    ]);
})->name('announcement.detail');

Route::get('/prestasi', function (Request $request) {
    $request->validate(['q' => ['nullable', 'string', 'max:120'], 'page' => ['nullable', 'integer', 'min:1']]);
    $query = mb_strtolower(trim((string) $request->query('q', '')));
    $items = collect(PublicContent::items('achievements'))->filter(fn (array $entry) => $query === '' || str_contains(mb_strtolower($entry['title'].' '.$entry['summary']), $query))->values();

    return view('himsi', ['page' => 'achievements', 'title' => 'Pencapaian & Prestasi', 'items' => (new LengthAwarePaginator($items->forPage($request->integer('page', 1), 6)->values(), $items->count(), 6, $request->integer('page', 1), ['path' => $request->url()]))->withQueryString()]);
})->name('achievements');

Route::get('/prestasi/{slug}', function (string $slug) {
    $item = collect(PublicContent::items('achievements'))->firstWhere('slug', $slug);
    abort_unless($item, 404);

    return view('himsi', ['page' => 'detail', 'title' => $item['title'], 'item' => $item, 'backRoute' => 'achievements', 'backLabel' => 'Prestasi']);
})->name('achievement.detail');

/*
|--------------------------------------------------------------------------
| LOGIN DAN RESET PASSWORD
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('himsi', [
            'page' => 'login',
            'title' => 'Login',
        ]);
    })->name('login');

    Route::post('/login', [
        HimsiAuthController::class,
        'login',
    ])
        ->middleware('throttle:5,1')
        ->name('login.submit');

    Route::get('/lupa-password', function () {
        return view('portal.forgot');
    })->name('password.request');

    Route::post('/lupa-password', [
        HimsiAuthController::class,
        'forgot',
    ])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [
        HimsiAuthController::class,
        'resetForm',
    ])->name('password.reset');

    Route::post('/reset-password', [
        HimsiAuthController::class,
        'reset',
    ])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

/*
|--------------------------------------------------------------------------
| DASHBOARD DAN LOGOUT
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    HimsiAccess::class,
])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $roleDashboard = ['kbh' => 'kbh.dashboard', 'ketua_umum' => 'ketua-umum.dashboard', 'sekretaris_umum' => 'sekretaris.dashboard', 'koordinator_pengawas' => 'pengawas.dashboard'];
        if (isset($roleDashboard[$request->user()->role])) {
            return redirect()->route($roleDashboard[$request->user()->role]);
        }

        if ($request->user()->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if (in_array($request->user()->role, ['koordinator_departemen', 'anggota_departemen'], true)) {
            $department = $request->user()->department;
            abort_unless(isset(config('superadmin.departments')[$department]), 403, 'Departemen akun belum ditentukan. Hubungi Super Admin.');

            if ($request->user()->role === 'koordinator_departemen' && ! PprkSubmission::approvedFor($department, config('department.period'))) {
                return redirect()->route('department.pprk');
            }

            return redirect()->route($request->user()->role === 'koordinator_departemen' ? 'department.dashboard' : 'department.member.dashboard', $department);
        }

        if ($request->user()->role === 'bendahara_umum') {
            return redirect()->route('finance.dashboard');
        }
        if (in_array($request->user()->role, ['sekretaris_umum', 'ketua_umum'], true)) {
            return redirect()->route('department.reviews');
        }

        if ($request->user()->role === 'panitia_cb' && Route::has('cb.dashboard')) {
            return redirect()->route('cb.dashboard');
        }

        if ($request->user()->role === 'panitia_kpum') {
            return redirect()->route('kpum.dashboard');
        }

        return view('himsi', [
            'page' => 'dashboard',
            'title' => 'Dashboard',
            'canVoteKpum' => DB::table('kpum_electors')->where('election_id', 1)->where('user_id', $request->user()->id)->exists(),
        ]);
    })->name('dashboard');

    Route::post('/logout', [
        HimsiAuthController::class,
        'logout',
    ])->name('logout');
});

/*
|--------------------------------------------------------------------------
| ROUTE SUPER ADMIN
|--------------------------------------------------------------------------
*/

require __DIR__.'/admin.php';
require __DIR__.'/portal.php';

require __DIR__.'/kpum.php';

if (is_file(__DIR__.'/cb.php')) {
    require __DIR__.'/cb.php';
}

require __DIR__.'/department.php';

require __DIR__.'/administration.php';

require __DIR__.'/finance.php';

Route::middleware(['auth', HimsiAccess::class])->prefix('rapat-himpunan')->name('meetings.')->controller(MeetingController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/rekap.pdf', 'export')->name('export');
    Route::get('/{meeting}', 'show')->whereNumber('meeting')->name('show');
    Route::post('/{meeting}/mulai-absen', 'open')->name('open');
    Route::get('/{meeting}/pembaruan', 'live')->name('live');
    Route::post('/{meeting}/absen', 'attend')->middleware('throttle:10,1')->name('attend');
    Route::post('/{meeting}/peserta/{attendance}', 'manual')->name('manual');
    Route::post('/{meeting}/tutup-absen', 'close')->name('close');
    Route::post('/{meeting}/notula', 'minutes')->name('minutes');
});

Route::post('/ruang-kerja', [WorkspaceController::class, 'update'])
    ->middleware(['auth', HimsiAccess::class])->name('workspace.update');

Route::middleware(['auth', HimsiAccess::class])->group(function () {
    Route::get('/selamat', [RoleWelcomeController::class, 'show'])->name('role.welcome');
    Route::post('/selamat', [RoleWelcomeController::class, 'acknowledge'])->name('role.welcome.acknowledge');
    Route::prefix('rekrutmen')->name('recruitment.')->controller(RecruitmentController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/buka', 'open')->name('open');
        Route::post('/{period}/buka-kembali', 'reopen')->name('reopen');
        Route::post('/{period}/tutup', 'close')->name('close');
        Route::post('/{period}/tetapkan', 'finalize')->name('finalize');
        Route::post('/peserta/{application}/verifikasi', 'review')->name('review');
        Route::post('/peserta/{application}/jadwal', 'schedule')->name('schedule');
        Route::get('/peserta/{application}/wawancara', 'interview')->name('interview');
        Route::post('/peserta/{application}/nilai', 'score')->name('score');
        Route::get('/peserta/{application}/berkas/{type}', 'file')->name('file');
        Route::get('/daftar', 'applyForm')->name('apply');
        Route::post('/daftar', 'apply')->name('store');
        Route::get('/hasil', 'result')->name('result');
    });
});

Route::middleware(['auth', HimsiAccess::class])->group(function (): void {
    Route::get('/arsip-kepengurusan/{type}/{record}/berkas/{field}', [GovernanceController::class, 'file'])->name('governance.file');
    Route::get('/arsip-kepengurusan', [GovernanceController::class, 'archive'])->name('governance.archive');
    Route::post('/arsip-kepengurusan/{type}/{record}/tindak-lanjut', [GovernanceController::class, 'followup'])->name('governance.followup');
});
