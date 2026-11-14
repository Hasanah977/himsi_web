<?php

namespace App\Http\Controllers;

use App\Models\SuratKeluar;
use App\Services\PratinjauDocxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenSuratKeluarController extends Controller
{
    private function authorizeAkses(
        Request $request,
        SuratKeluar $suratKeluar
    ): void {
        $user = $request->user();

        abort_unless($user, 401);

        $role = $user->administration_role;

        if ($role === 'Sekertaris') {
            return;
        }

        if ($role === 'Ketua Himpunan') {
            abort_unless(
                in_array($suratKeluar->status, [
                    'menunggu_persetujuan',
                    'disetujui',
                    'ditolak',
                ], true),
                403,
                'Surat belum diajukan untuk persetujuan.'
            );

            return;
        }

        if ($suratKeluar->status === 'disetujui'
            && (int) $suratKeluar->penandatangan_pendamping_id === (int) $user->id) {
            return;
        }
        abort(403, 'Anda tidak memiliki akses dokumen ini.');
    }

    private function filePath(SuratKeluar $suratKeluar): string
    {
        abort_unless(
            $suratKeluar->file_surat,
            404,
            'Dokumen surat belum tersedia.'
        );

        $path = $suratKeluar->file_surat;

        abort_unless(
            str_starts_with($path, 'surat-keluar/')
                && ! str_contains($path, '..')
                && Storage::disk('local')->exists($path),
            404,
            'Dokumen surat tidak ditemukan.'
        );

        return $path;
    }

    public function lihat(
        Request $request,
        SuratKeluar $suratKeluar
    ) {
        $this->authorizeAkses($request, $suratKeluar);

        $path = $this->filePath($suratKeluar);
        $disk = Storage::disk('local');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        // Word dipratinjau sebagai HTML teks/tabel, TANPA mengubah berkas asli.
        // Hanya endpoint Unduh yang boleh memulai unduhan.
        if (in_array($ext, ['doc', 'docx'], true)) {
            return PratinjauDocxService::lihat($disk->path($path), $ext);
        }

        $mime = $disk->mimeType($path);

        abort_unless(
            in_array($mime, [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ], true),
            415,
            'Format dokumen tidak didukung.'
        );

        return response()->file(
            $disk->path($path),
            [
                'Content-Type' => $mime,
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
        $this->authorizeAkses($request, $suratKeluar);

        $path = $this->filePath($suratKeluar);

        return Storage::disk('local')->download(
            $path,
            basename($path)
        );
    }
}
