<?php

use App\Http\Controllers\Administration\FollowUpController;
use App\Http\Controllers\Administration\PortalController;
use App\Http\Controllers\Administration\SelectionController;
use App\Http\Controllers\Bendahara\VerifikasiIuranController;
use App\Http\Controllers\BuktiIuranController;
use App\Http\Controllers\DokumenFinalSuratKeluarController;
use App\Http\Controllers\DokumenSuratKeluarController;
use App\Http\Controllers\kbh\AspirasiController;
use App\Http\Controllers\kbh\IuranController;
use App\Http\Controllers\kbh\ProfilController;
use App\Http\Controllers\kbh\SeleksiController;
use App\Http\Controllers\KetuaUmum\ApprovalCenterController;
use App\Http\Controllers\KetuaUmum\EskalasiPengawasController;
use App\Http\Controllers\KetuaUmum\PersetujuanSuratController;
use App\Http\Controllers\KetuaUmum\TandaTanganController;
use App\Http\Controllers\KoordinatorPengawas\PengawasanController;
use App\Http\Controllers\Kpum\VoteController;
use App\Http\Controllers\NotifikasiHimsiController;
use App\Http\Controllers\PersetujuanTandaTanganController;
use App\Http\Controllers\Sekretaris\DokumenFinalSuratController;
use App\Http\Controllers\Sekretaris\SuratKeluarController;
use App\Http\Controllers\Sekretaris\SuratMasukController;
use App\Http\Controllers\TandaTanganSuratController;
use App\Http\Middleware\AdministrationAccess;
use App\Http\Middleware\EnsureKbhRole;
use App\Http\Middleware\EnsureKetuaUntukEskalasi;
use App\Http\Middleware\EnsureKoordinatorPengawas;
use App\Http\Middleware\HimsiAccess;
use App\Models\Pengajuan;
use App\Models\TandaTangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware(['auth', HimsiAccess::class, AdministrationAccess::class])->group(function () {
    Route::middleware(['auth', EnsureKbhRole::class])
        ->prefix('kbh')
        ->name('kbh.')
        ->group(function () {
            Route::post('/profil/foto', [ProfilController::class, 'updatePhoto'])->name('profil.photo.update');
            Route::get('/profil/foto', [ProfilController::class, 'photo'])->name('profil.photo');
            Route::get('/kegiatan/{program}', [PortalController::class, 'activity'])->name('activity');

            /*
            |--------------------------------------------------------------------------
            | DASHBOARD
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/dashboard',
                [PortalController::class, 'dashboard']
            )->name('dashboard');

            /*
            |--------------------------------------------------------------------------
            | PENGUMUMAN
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/pengumuman',
                [PortalController::class, 'announcements']
            )->name('pengumuman.index');

            Route::get(
                '/pengumuman/{id}',
                [PortalController::class, 'announcement']
            )->name('pengumuman.show');

            /*
            |--------------------------------------------------------------------------
            | PROGRAM KERJA
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/program-kerja',
                [PortalController::class, 'publicPrograms']
            )->name('program-kerja.index');

            Route::get(
                '/program-kerja/{id}',
                [PortalController::class, 'publicProgram']
            )->name('program-kerja.show');

            /*
            |--------------------------------------------------------------------------
            | VOTING
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/voting',
                [PortalController::class, 'voting']
            )->name('voting.index');

            Route::get(
                '/voting/{id}/visi-misi',
                [PortalController::class, 'candidate']
            )->name('voting.visi-misi');

            Route::post(
                '/voting',
                [VoteController::class, 'store']
            )->name('voting.vote');

            Route::get(
                '/hasil-voting',
                [PortalController::class, 'voting']
            )->name('voting.hasil');

            /*
            |--------------------------------------------------------------------------
            | SELEKSI PENGURUS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/seleksi',
                [SeleksiController::class, 'index']
            )->name('seleksi.index');

            Route::get(
                '/seleksi/upload',
                [SeleksiController::class, 'upload']
            )->name('seleksi.upload');

            Route::post(
                '/seleksi/upload',
                [SeleksiController::class, 'store']
            )->name('seleksi.store');

            Route::get(
                '/seleksi/hasil',
                [SeleksiController::class, 'hasil']
            )->name('seleksi.hasil');

            /*
            |--------------------------------------------------------------------------
            | PROFIL
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/profil',
                [ProfilController::class, 'index']
            )->name('profil');

            Route::get(
                '/profil/edit',
                [ProfilController::class, 'edit']
            )->name('profil.edit');

            Route::put(
                '/profil',
                [ProfilController::class, 'update']
            )->name('profil.update');

            Route::get(
                '/profil/password',
                [ProfilController::class, 'password']
            )->name('profil.password');

            Route::put(
                '/profil/password',
                [ProfilController::class, 'updatePassword']
            )->name('profil.password.update');

            /*
            |--------------------------------------------------------------------------
            | IURAN
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/iuran',
                [IuranController::class, 'index']
            )->name('iuran.index');

            Route::get('/iuran/bayar/{bulan}', [IuranController::class, 'bayar'])
                ->whereNumber('bulan')->name('iuran.bayar');
            Route::post('/iuran/bayar/{bulan}', [IuranController::class, 'store'])
                ->whereNumber('bulan')->name('iuran.store');

            /*
            |--------------------------------------------------------------------------
            | ASPIRASI
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/aspirasi',
                [AspirasiController::class, 'index']
            )->name('aspirasi.index');

            Route::post(
                '/aspirasi',
                [AspirasiController::class, 'store']
            )->name('aspirasi.store');

            /*
            |--------------------------------------------------------------------------
            | NOTIFIKASI
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/notifikasi',
                [PortalController::class, 'notifications']
            )->name('notifikasi.index');

        });

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD KETUA UMUM
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth')
        ->prefix('ketua-umum')
        ->name('ketua-umum.')
        ->group(function () {

            Route::get('/dashboard', function () {

                abort_unless(
                    auth()->user()->administration_role === 'Ketua Himpunan',
                    403
                );

                return app(PortalController::class)->dashboard(request());

            })->name('dashboard');

            Route::get('/monitoring-organisasi', function () {
                abort_unless(
                    auth()->user()->administration_role === 'Ketua Himpunan',
                    403
                );

                return app(PortalController::class)->programs(request());
            })->name('monitoring-organisasi');

            Route::get('/approval-center', function () {
                abort_unless(
                    auth()->user()->administration_role === 'Ketua Himpunan',
                    403
                );

                return app(ApprovalCenterController::class)->index();
            })->name('approval-center');

            Route::get('/tanda-tangan', function () {
                abort_unless(
                    auth()->user()?->administration_role === 'Ketua Himpunan',
                    403
                );

                return app(TandaTanganController::class)->index();
            })->name('tanda-tangan');

            Route::get('/tanda-tangan/gambar', function (Request $request) {
                abort_unless(
                    $request->user()?->administration_role === 'Ketua Himpunan',
                    403
                );

                $tandaTangan = TandaTangan::where(
                    'user_id',
                    $request->user()->id
                )->firstOrFail();

                $disk = Storage::disk('local');

                abort_unless($disk->exists($tandaTangan->file_path), 404);

                return response($disk->get($tandaTangan->file_path), 200, [
                    'Content-Type' => 'image/png',
                    'Cache-Control' => 'private, no-store',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            })->name('tanda-tangan.gambar');
            Route::post('/tanda-tangan/simpan', function (
                Request $request
            ) {
                abort_unless(
                    $request->user()?->administration_role === 'Ketua Himpunan',
                    403
                );

                return app(TandaTanganController::class)
                    ->simpan($request);
            })->name('tanda-tangan.simpan');

            Route::post('/approval-center/{pengajuan}/proses', function (
                Request $request,
                Pengajuan $pengajuan
            ) {
                abort_unless(
                    $request->user()?->administration_role === 'Ketua Himpunan',
                    403
                );

                return app(ApprovalCenterController::class)
                    ->proses($request, $pengajuan);
            })->name('approval-center.proses');

        });

    // ======================================================
    // SEKRETARIS UMUM HIMSI
    // ======================================================

    Route::middleware('auth')
        ->prefix('sekretaris')
        ->name('sekretaris.')
        ->group(function () {

            Route::get('/dashboard', [
                PortalController::class,
                'dashboard',
            ])->name('dashboard');

        });
    /*
    |--------------------------------------------------------------------------
    | SEKRETARIS UMUM - SURAT MASUK
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth')
        ->prefix('sekretaris')
        ->name('sekretaris.')
        ->group(function () {
            Route::resource(
                'surat-masuk',
                SuratMasukController::class
            )->parameters([
                'surat-masuk' => 'suratMasuk',
            ]);
        });

    /*
    |--------------------------------------------------------------------------
    | SEKRETARIS - DOKUMEN SURAT MASUK
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth')
        ->prefix('sekretaris/surat-masuk')
        ->name('sekretaris.surat-masuk.')
        ->group(function () {
            Route::get(
                '{suratMasuk}/lihat-dokumen',
                [SuratMasukController::class, 'lihatDokumen']
            )->name('lihat-dokumen');

            Route::get(
                '{suratMasuk}/unduh-dokumen',
                [SuratMasukController::class, 'unduhDokumen']
            )->name('unduh-dokumen');
        });

    Route::middleware('auth')
        ->get(
            '/sekretaris/surat-keluar',
            [SuratKeluarController::class, 'index']
        )
        ->name('sekretaris.surat-keluar.index');

    Route::middleware('auth')
        ->get(
            '/sekretaris/surat-keluar/create',
            [SuratKeluarController::class, 'create']
        )
        ->name('sekretaris.surat-keluar.create');

    Route::middleware('auth')
        ->post(
            '/sekretaris/surat-keluar',
            [SuratKeluarController::class, 'store']
        )
        ->name('sekretaris.surat-keluar.store');

    Route::middleware('auth')
        ->get(
            '/sekretaris/surat-keluar/{suratKeluar}',
            [SuratKeluarController::class, 'show']
        )
        ->whereNumber('suratKeluar')
        ->name('sekretaris.surat-keluar.show');

    Route::middleware('auth')
        ->get(
            '/sekretaris/surat-keluar/{suratKeluar}/edit',
            [SuratKeluarController::class, 'edit']
        )
        ->whereNumber('suratKeluar')
        ->name('sekretaris.surat-keluar.edit');

    Route::middleware('auth')
        ->put(
            '/sekretaris/surat-keluar/{suratKeluar}',
            [SuratKeluarController::class, 'update']
        )
        ->whereNumber('suratKeluar')
        ->name('sekretaris.surat-keluar.update');

    Route::middleware('auth')
        ->post(
            '/sekretaris/surat-keluar/{suratKeluar}/ajukan',
            [SuratKeluarController::class, 'ajukanPersetujuan']
        )
        ->whereNumber('suratKeluar')
        ->name('sekretaris.surat-keluar.ajukan');

    Route::middleware('auth')
        ->get(
            '/ketua-umum/persetujuan-surat',
            [PersetujuanSuratController::class, 'index']
        )
        ->name('ketua-umum.persetujuan-surat.index');

    Route::middleware('auth')
        ->get(
            '/ketua-umum/persetujuan-surat/{suratKeluar}',
            [PersetujuanSuratController::class, 'show']
        )
        ->whereNumber('suratKeluar')
        ->name('ketua-umum.persetujuan-surat.show');

    Route::middleware('auth')
        ->post(
            '/ketua-umum/persetujuan-surat/{suratKeluar}/putuskan',
            [PersetujuanSuratController::class, 'putuskan']
        )
        ->whereNumber('suratKeluar')
        ->name('ketua-umum.persetujuan-surat.putuskan');

    // Arsip PDF final setelah persetujuan Ketua Umum
    Route::middleware('auth')
        ->post(
            '/sekretaris/surat-keluar/{suratKeluar}/dokumen-final',
            [DokumenFinalSuratController::class, 'simpan']
        )
        ->whereNumber('suratKeluar')
        ->name('sekretaris.surat-keluar.dokumen-final.simpan');

    // Akses PDF final yang sudah disahkan sesuai SOP HIMSI
    Route::middleware('auth')->group(function () {
        Route::get(
            '/surat-keluar/{suratKeluar}/dokumen-final/lihat',
            [DokumenFinalSuratKeluarController::class, 'lihat']
        )
            ->whereNumber('suratKeluar')
            ->name('surat-keluar.dokumen-final.lihat');

        Route::get(
            '/surat-keluar/{suratKeluar}/dokumen-final/unduh',
            [DokumenFinalSuratKeluarController::class, 'unduh']
        )
            ->whereNumber('suratKeluar')
            ->name('surat-keluar.dokumen-final.unduh');
    });

    // Dokumen Surat Keluar - Sekretaris dan Ketua Himpunan
    Route::middleware('auth')->group(function () {
        Route::get(
            '/surat-keluar/{suratKeluar}/dokumen/lihat',
            [DokumenSuratKeluarController::class, 'lihat']
        )->whereNumber('suratKeluar')
            ->name('surat-keluar.dokumen.lihat');

        Route::get(
            '/surat-keluar/{suratKeluar}/dokumen/unduh',
            [DokumenSuratKeluarController::class, 'unduh']
        )->whereNumber('suratKeluar')
            ->name('surat-keluar.dokumen.unduh');
    });

    // Revisi asistensi 19 Oktober: arsip, persetujuan penandatangan, dan notifikasi terintegrasi.
    Route::middleware('auth')->group(function () {
        Route::get('/sekretaris/arsip-surat', [SuratKeluarController::class, 'arsip'])
            ->name('sekretaris.arsip-surat.index');
        Route::post('/sekretaris/surat-keluar/{suratKeluar}/arsipkan', [SuratKeluarController::class, 'arsipkan'])
            ->whereNumber('suratKeluar')->name('sekretaris.surat-keluar.arsipkan');
        Route::post('/sekretaris/surat-keluar/{suratKeluar}/pulihkan-arsip', [SuratKeluarController::class, 'pulihkanArsip'])
            ->whereNumber('suratKeluar')->name('sekretaris.surat-keluar.pulihkan-arsip');
        Route::get('/tanda-tangan/permintaan', [PersetujuanTandaTanganController::class, 'index'])
            ->name('tanda-tangan.permintaan');
        Route::get('/tanda-tangan/permintaan/{suratKeluar}', [PersetujuanTandaTanganController::class, 'show'])
            ->whereNumber('suratKeluar')->name('tanda-tangan.persetujuan.show');
        Route::post('/tanda-tangan/permintaan/{suratKeluar}/putuskan', [PersetujuanTandaTanganController::class, 'putuskan'])
            ->whereNumber('suratKeluar')->name('tanda-tangan.persetujuan.putuskan');
        Route::post('/tanda-tangan/gambar', [PersetujuanTandaTanganController::class, 'simpanGambar'])
            ->name('tanda-tangan.pribadi.simpan');
        Route::get('/tanda-tangan/gambar', [PersetujuanTandaTanganController::class, 'gambar'])
            ->name('tanda-tangan.pribadi.gambar');
        Route::post('/notifikasi-himsi/baca-semua', [NotifikasiHimsiController::class, 'readAll'])->name('notifikasi-himsi.read-all');
        Route::delete('/notifikasi-himsi/{notifikasi}', [NotifikasiHimsiController::class, 'destroy'])->name('notifikasi-himsi.destroy');
        Route::post('/notifikasi-himsi/{notifikasi}/baca', [NotifikasiHimsiController::class, 'baca'])
            ->name('notifikasi-himsi.baca');
    });

    Route::middleware('auth')->get('/sekretaris/pengajuan-tanda-tangan',
        [SuratKeluarController::class, 'pengajuanTandaTangan'])
        ->name('sekretaris.pengajuan-tanda-tangan.index');

    // HIMSI 2026: posisi tanda tangan ditetapkan Sekretaris sebelum surat diajukan.
    Route::middleware('auth')->post(
        '/sekretaris/surat-keluar/{suratKeluar}/posisi-tanda-tangan',
        [SuratKeluarController::class, 'simpanPosisiTandaTangan']
    )->whereNumber('suratKeluar')->name('sekretaris.surat-keluar.posisi-ttd.simpan');

    // HIMSI: setiap pejabat menempel gambar TTD miliknya sendiri pada surat PDF.
    Route::middleware('auth')->group(function () {
        Route::get('/tanda-tangan/surat/{suratKeluar}/editor', [TandaTanganSuratController::class, 'edit'])
            ->whereNumber('suratKeluar')->name('tanda-tangan.surat.editor');
        Route::post('/tanda-tangan/surat/{suratKeluar}/simpan', [TandaTanganSuratController::class, 'simpan'])
            ->whereNumber('suratKeluar')->name('tanda-tangan.surat.simpan');
        Route::get('/tanda-tangan/surat/{suratKeluar}/gambar', [TandaTanganSuratController::class, 'gambar'])
            ->whereNumber('suratKeluar')->name('tanda-tangan.surat.gambar');
    });

    // Lampiran aspirasi KBH: akses hanya oleh pemiliknya.
    Route::middleware(['auth', EnsureKbhRole::class])
        ->get('/kbh/aspirasi/{aspirasi}/lampiran/{aksi}', [AspirasiController::class, 'lampiran'])
        ->whereIn('aksi', ['lihat', 'unduh'])
        ->name('kbh.aspirasi.lampiran');

    // Revisi asistensi: verifikasi bukti iuran oleh akun ber-role Bendahara.
    Route::middleware('auth')->group(function () {
        Route::get('/iuran/bukti/{pembayaran}', [BuktiIuranController::class, 'lihat'])
            ->whereNumber('pembayaran')->name('iuran.bukti.lihat');
        Route::get('/iuran/bukti/{pembayaran}/unduh', [BuktiIuranController::class, 'unduh'])
            ->whereNumber('pembayaran')->name('iuran.bukti.unduh');

        Route::prefix('bendahara/iuran')->name('bendahara.iuran.')->group(function () {
            Route::get('/verifikasi', [VerifikasiIuranController::class, 'index'])->name('index');
            Route::post('/verifikasi/{pembayaran}', [VerifikasiIuranController::class, 'putuskan'])
                ->whereNumber('pembayaran')->name('putuskan');
        });
    });

    // Tambahan Koordinator Pengawas HIMSI - Modul Laporan, Audit, Rekomendasi & Eskalasi.
    Route::middleware(['auth', EnsureKoordinatorPengawas::class])
        ->prefix('pengawas')->name('pengawas.')->group(function () {
            $c = PengawasanController::class;
            Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');
            Route::get('/laporan', [$c, 'laporanMasuk'])->name('laporan.index');
            Route::get('/laporan/buat', [$c, 'buatLaporan'])->name('laporan.create');
            Route::post('/laporan', [$c, 'simpanLaporan'])->name('laporan.store');
            Route::get('/laporan/{laporan}', [$c, 'detail'])->name('laporan.show');
            Route::get('/laporan/{laporan}/lampiran/{aksi}', [$c, 'lampiran'])->name('laporan.lampiran');
            Route::post('/laporan/{laporan}/audit', [$c, 'simpanAudit'])->name('laporan.audit');
            Route::post('/laporan/{laporan}/rekomendasi', [$c, 'simpanRekomendasi'])->name('laporan.rekomendasi');
            Route::post('/laporan/{laporan}/eskalasi', [$c, 'simpanEskalasi'])->name('laporan.eskalasi');
            Route::get('/aktif', [$c, 'pengawasanAktif'])->name('aktif.index');
            Route::get('/hasil-audit', [$c, 'hasilAudit'])->name('audit.index');
            Route::get('/rekomendasi', [$c, 'rekomendasi'])->name('rekomendasi.index');
            Route::post('/rekomendasi/{rekomendasi}/tindak-lanjut', [$c, 'tindakLanjut'])->name('rekomendasi.tindak-lanjut');
            Route::get('/eskalasi', [$c, 'eskalasi'])->name('eskalasi.index');
        });

    // Ketua Umum berwenang memutuskan eskalasi, bukan Koordinator Pengawas.
    Route::middleware(['auth', EnsureKetuaUntukEskalasi::class])
        ->prefix('ketua-umum/eskalasi-pengawas')->name('ketua-umum.eskalasi-pengawas.')->group(function () {
            $c = EskalasiPengawasController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('/{eskalasi}/putuskan', [$c, 'putuskan'])->name('putuskan');
        });
});

Route::middleware(['auth', HimsiAccess::class])->prefix('administrasi')->name('administration.')->group(function () {
    Route::get('/program', [PortalController::class, 'programs'])->name('programs');
    Route::get('/program/{program}', [PortalController::class, 'program'])->name('program');
    Route::get('/notifikasi', [PortalController::class, 'notifications'])->name('notifications');
});

Route::middleware(['auth', HimsiAccess::class])->prefix('administrasi/seleksi')->name('administration.selection')->group(function () {
    $c = SelectionController::class;
    Route::get('/', [$c, 'index'])->name('');
    Route::post('/periode', [$c, 'period'])->name('.period');
    Route::post('/periode/{period}/tutup', [$c, 'close'])->name('.close');
    Route::post('/{application}/penilaian', [$c, 'review'])->name('.review');
    Route::get('/{application}/berkas/{type}', [$c, 'file'])->name('.file');
});

Route::middleware(['auth', HimsiAccess::class])->prefix('administrasi')->name('administration.')->group(function () {
    $c = FollowUpController::class;
    Route::get('/aspirasi', [$c, 'aspirations'])->name('aspirations');
    Route::post('/aspirasi/{aspiration}/tanggapan', [$c, 'respond'])->name('aspirations.respond');
    Route::get('/aspirasi/{aspiration}/lampiran', [$c, 'aspirationFile'])->name('aspirations.file');
    Route::get('/rekomendasi', [$c, 'recommendations'])->name('recommendations');
    Route::post('/rekomendasi/{recommendation}/tanggapan', [$c, 'followUp'])->name('recommendations.respond');
});
