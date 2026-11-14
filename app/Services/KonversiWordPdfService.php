<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class KonversiWordPdfService
{
    /** Hasil PDF disimpan privat. File Word asli tidak diubah. */
    public function buatPratinjau(string $fileAsli, string $folder): ?string
    {
        $ext = strtolower(pathinfo($fileAsli, PATHINFO_EXTENSION));
        if ($ext === 'pdf' || in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return null;
        }
        abort_unless(in_array($ext, ['doc', 'docx'], true), 422, 'Format dokumen tidak didukung.');

        $exe = $this->lokasiLibreOffice();
        abort_unless($exe !== null, 422,
            'Konversi Word belum tersedia. Instal LibreOffice pada Windows dan atur LIBREOFFICE_PATH di .env, lalu coba lagi.');

        $folderSementara = storage_path('app/konversi-himsi/'.Str::uuid());
        File::ensureDirectoryExists($folderSementara);
        $input = $folderSementara.'/dokumen.'.$ext;
        $output = $folderSementara.'/dokumen.pdf';
        try {
            abort_unless(Storage::disk('local')->exists($fileAsli), 422, 'Berkas asli tidak ditemukan.');
            abort_unless(copy(Storage::disk('local')->path($fileAsli), $input), 500, 'Gagal menyiapkan konversi dokumen.');
            $profile = 'file:///'.str_replace('\\', '/', $folderSementara.'/profile');
            $process = new Process([
                $exe, '-env:UserInstallation='.$profile,
                '--headless', '--convert-to', 'pdf:writer_pdf_Export', '--outdir', $folderSementara, $input,
            ]);
            $process->setTimeout(90);
            $process->run();
            abort_unless($process->isSuccessful() && is_file($output) && filesize($output) > 50, 422,
                'Konversi Word ke PDF gagal. Periksa LibreOffice atau format file Word yang diunggah.');
            $header = file_get_contents($output, false, null, 0, 5);
            abort_unless($header === '%PDF-', 422, 'Hasil konversi bukan PDF yang valid.');
            $path = $folder.'/pratinjau/'.Str::uuid().'.pdf';
            abort_unless(Storage::disk('local')->put($path, file_get_contents($output)), 500,
                'Pratinjau PDF gagal disimpan.');

            return $path;
        } finally {
            File::deleteDirectory($folderSementara);
        }
    }

    private function lokasiLibreOffice(): ?string
    {
        $candidates = [
            'E:/program/soffice.exe',
            env('LIBREOFFICE_PATH'),
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        ];
        foreach ($candidates as $path) {
            if ($path && is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
