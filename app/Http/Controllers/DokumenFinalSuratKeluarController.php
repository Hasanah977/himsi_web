<?php

namespace App\Http\Controllers;

use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenFinalSuratKeluarController extends Controller
{
    private function filePath(
        Request $request,
        SuratKeluar $suratKeluar
    ): string {
        $user = $request->user();

        abort_unless($user, 401);

        $role = $user->administration_role;

        abort_unless(
            in_array($role, [
                'Sekertaris',
                'Ketua Himpunan',
            ], true),
            403,
            'Anda tidak memiliki akses dokumen final.'
        );

        abort_unless(
            $suratKeluar->status === 'disetujui'
                && filled($suratKeluar->file_final),
            404,
            'Dokumen final belum tersedia.'
        );

        $path = $suratKeluar->file_final;

        abort_unless(
            str_starts_with($path, 'surat-keluar/final/')
                && ! str_contains($path, '..')
                && Storage::disk('local')->exists($path),
            404,
            'File dokumen final tidak ditemukan.'
        );

        return $path;
    }

    public function lihat(
        Request $request,
        SuratKeluar $suratKeluar
    ) {
        $path = $this->filePath($request, $suratKeluar);

        return response()->file(
            Storage::disk('local')->path($path),
            [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "frame-ancestors 'self'",
                'X-Frame-Options' => 'SAMEORIGIN',
                'Content-Disposition' => 'inline',
            ]
        );
    }

    public function unduh(
        Request $request,
        SuratKeluar $suratKeluar
    ) {
        $path = $this->filePath($request, $suratKeluar);

        return Storage::disk('local')->download(
            $path,
            'surat-final-'.$suratKeluar->id.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
