<?php

namespace App\Services;

use App\Models\SuratKeluar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class PengesahanPdfService
{
    /** Buat salinan PDF final menggunakan gambar yang diunggah masing-masing penandatangan. */
    public function terbitkan(SuratKeluar $surat): SuratKeluar
    {
        $pathFinal = null;
        $disk = Storage::disk('local');
        try {
            DB::transaction(function () use ($surat, $disk, &$pathFinal) {
                $surat = SuratKeluar::query()->lockForUpdate()->findOrFail($surat->getKey());
                abort_unless($surat->status === 'disetujui' && $surat->disetujui_oleh && $surat->nomor_surat,
                    422, 'Surat belum disetujui Ketua Umum atau belum bernomor resmi.');
                abort_if($surat->file_final, 409, 'PDF final sudah tersedia, tidak boleh ditimpa.');
                $asli = $surat->file_surat;
                abort_unless($asli && str_starts_with($asli, 'surat-keluar/') &&
                    ! str_contains($asli, '..') && strtolower(pathinfo($asli, PATHINFO_EXTENSION)) === 'pdf' &&
                    $disk->exists($asli), 422, 'Hanya surat PDF asli yang dapat ditempeli gambar TTD. Word tidak dikonversi.');
                abort_unless(file_get_contents($disk->path($asli), false, null, 0, 5) === '%PDF-',
                    422, 'File asli bukan PDF yang valid.');

                $ids = [(int) $surat->disetujui_oleh];
                if ($surat->penandatangan_pendamping_id) {
                    $pendamping = (int) $surat->penandatangan_pendamping_id;
                    $izin = DB::table('persetujuan_tanda_tangan_surats')
                        ->where('surat_keluar_id', $surat->id)
                        ->where('user_id', $pendamping)
                        ->where('status', 'disetujui')->exists();
                    abort_unless($izin, 422, 'Pejabat pendamping belum menyetujui TTD.');
                    $ids[] = $pendamping;
                }
                $placements = DB::table('tanda_tangan_surat_penempatans')
                    ->where('surat_keluar_id', $surat->id)
                    ->whereIn('user_id', $ids)->get()->keyBy('user_id');
                foreach ($ids as $id) {
                    abort_unless($placements->has($id), 422,
                        'Pejabat yang wajib menandatangani belum mengunggah dan mengatur posisi gambarnya sendiri.');
                }
                $pdf = new Fpdi('P', 'mm');
                $count = $pdf->setSourceFile($disk->path($asli));
                abort_unless($count > 0, 422, 'PDF tidak memiliki halaman.');
                $signs = [];
                foreach ($ids as $id) {
                    $p = $placements->get($id);
                    $path = $p->gambar_path;
                    abort_unless(str_starts_with($path, 'tanda-tangan-surat/') &&
                        ! str_contains($path, '..') && $disk->exists($path), 422,
                        'File TTD salah satu pejabat tidak tersedia.');
                    $file = $disk->path($path);
                    $meta = @getimagesize($file);
                    abort_unless($meta && in_array($meta[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true),
                        422, 'File TTD bukan PNG/JPG yang valid.');
                    abort_unless($p->halaman >= 1 && $p->halaman <= $count, 422,
                        'Nomor halaman tanda tangan salah.');
                    $signs[] = [$p, $file, $meta];
                }
                for ($i = 1; $i <= $count; $i++) {
                    $tpl = $pdf->importPage($i);
                    $page = $pdf->getTemplateSize($tpl);
                    $pdf->AddPage($page['orientation'], [$page['width'], $page['height']]);
                    $pdf->useTemplate($tpl);
                    foreach ($signs as [$p, $file, $meta]) {
                        if ((int) $p->halaman !== $i) {
                            continue;
                        }
                        $x = (float) $p->x_mm;
                        $y = (float) $p->y_mm;
                        $w = (float) $p->lebar_mm;
                        $h = $w * $meta[1] / max(1, $meta[0]);
                        abort_unless($x >= 0 && $y >= 0 && $w >= 15 && $w <= 80 &&
                            $x + $w <= $page['width'] + .01 && $y + $h <= $page['height'] + .01,
                            422, 'Posisi TTD melewati batas halaman PDF.');
                        $pdf->Image($file, $x, $y, $w, $h,
                            $meta[2] === IMAGETYPE_PNG ? 'PNG' : 'JPG');
                    }
                }
                $pathFinal = 'surat-keluar/final/surat-'.$surat->id.'-'.Str::uuid().'.pdf';
                abort_unless($disk->put($pathFinal, $pdf->Output('S')),
                    500, 'Gagal menyimpan PDF final.');
                $surat->file_final = $pathFinal;
                $surat->save();
            }, 3);
        } catch (\Throwable $e) {
            if ($pathFinal) {
                $disk->delete($pathFinal);
            }
            throw $e;
        }

        return $surat->fresh();
    }
}
