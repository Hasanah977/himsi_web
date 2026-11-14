<?php

namespace App\Http\Controllers;

use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class TandaTanganSuratController extends Controller
{
    private function periksaAkses(Request $request, SuratKeluar $surat, bool $mengubah = false): void
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless(! $surat->file_final, 409, 'PDF final sudah terbit dan tidak dapat diubah.');
        $ketua = $user->administration_role === 'Ketua Himpunan';
        if ($ketua) {
            abort_unless(
                $surat->status === 'menunggu_persetujuan'
                || ($surat->status === 'disetujui' && (int) $surat->disetujui_oleh === (int) $user->id),
                403, 'Hanya Ketua Umum yang sedang menangani surat ini yang boleh menandatangani.'
            );
            if ($mengubah) {
                abort_unless($surat->status === 'menunggu_persetujuan', 409,
                    'Posisi TTD Ketua sudah dikunci setelah keputusan.');
            }
        } else {
            abort_unless($surat->status === 'disetujui' &&
                (int) $surat->penandatangan_pendamping_id === (int) $user->id,
                403, 'Hanya pejabat pendamping yang ditunjuk boleh menandatangani.');
            $izin = DB::table('persetujuan_tanda_tangan_surats')
                ->where('surat_keluar_id', $surat->id)->where('user_id', $user->id)->first();
            abort_unless($izin && $izin->status !== 'ditolak', 403, 'Permintaan TTD tidak aktif.');
            if ($mengubah) {
                abort_unless($izin->status === 'menunggu', 409,
                    'Posisi TTD pendamping dikunci setelah memberi keputusan.');
            }
        }
        $path = $surat->file_surat;
        abort_unless($path && str_starts_with($path, 'surat-keluar/') &&
            ! str_contains($path, '..') && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf' &&
            Storage::disk('local')->exists($path), 422,
            'Penempelan tanda tangan langsung hanya tersedia untuk dokumen PDF asli. Word tetap bisa diajukan tanpa konversi.');
    }

    public function edit(Request $request, SuratKeluar $suratKeluar)
    {
        $this->periksaAkses($request, $suratKeluar);
        $penempatan = DB::table('tanda_tangan_surat_penempatans')
            ->where('surat_keluar_id', $suratKeluar->id)
            ->where('user_id', $request->user()->id)->first();
        $ketua = $request->user()->administration_role === 'Ketua Himpunan';
        $terkunci = $ketua ? $suratKeluar->status !== 'menunggu_persetujuan' :
            DB::table('persetujuan_tanda_tangan_surats')
                ->where('surat_keluar_id', $suratKeluar->id)
                ->where('user_id', $request->user()->id)
                ->where('status', 'menunggu')->doesntExist();

        return view('tanda-tangan.editor-surat', compact('suratKeluar', 'penempatan', 'ketua', 'terkunci'));
    }

    public function gambar(Request $request, SuratKeluar $suratKeluar)
    {
        $this->periksaAkses($request, $suratKeluar);
        $penempatan = DB::table('tanda_tangan_surat_penempatans')
            ->where('surat_keluar_id', $suratKeluar->id)
            ->where('user_id', $request->user()->id)->first();
        abort_unless($penempatan, 404);
        $path = $penempatan->gambar_path;
        abort_unless(str_starts_with($path, 'tanda-tangan-surat/') &&
            ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);
        $mime = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'png' ? 'image/png' : 'image/jpeg';

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self'",
        ]);
    }

    public function simpan(Request $request, SuratKeluar $suratKeluar)
    {
        $this->periksaAkses($request, $suratKeluar, true);
        $data = $request->validate([
            'gambar' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:1024'],
            'halaman' => ['required', 'integer', 'min:1', 'max:1000'],
            'x_mm' => ['required', 'numeric', 'min:0', 'max:500'],
            'y_mm' => ['required', 'numeric', 'min:0', 'max:500'],
            'lebar_mm' => ['required', 'numeric', 'min:15', 'max:80'],
            'konfirmasi_gambar' => ['required', 'accepted'],
        ]);
        $disk = Storage::disk('local');
        $lama = DB::table('tanda_tangan_surat_penempatans')
            ->where('surat_keluar_id', $suratKeluar->id)->where('user_id', $request->user()->id)->first();
        abort_unless($lama || $request->hasFile('gambar'), 422,
            'Pilih gambar tanda tangan milik Anda dari File Explorer.');
        $foto = $request->file('gambar');
        $newPath = null;
        if ($foto) {
            $meta = @getimagesize($foto->getRealPath());
            abort_unless($meta && in_array($meta[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true),
                422, 'Tanda tangan wajib berupa gambar PNG atau JPG yang valid.');
            $ext = $meta[2] === IMAGETYPE_PNG ? 'png' : 'jpg';
            $newPath = $foto->storeAs('tanda-tangan-surat', Str::uuid().'.'.$ext, 'local');
            abort_unless($newPath, 500, 'Gagal menyimpan file tanda tangan.');
        }
        $pathGambar = $newPath ?: $lama->gambar_path;
        try {
            $fullPath = $disk->path($pathGambar);
            $meta = @getimagesize($fullPath);
            abort_unless($meta, 422, 'Gambar tanda tangan rusak.');
            $pdf = new Fpdi('P', 'mm');
            $pages = $pdf->setSourceFile($disk->path($suratKeluar->file_surat));
            $halaman = (int) $data['halaman'];
            abort_unless($halaman <= $pages, 422, 'Halaman PDF tidak ditemukan.');
            $page = $pdf->getTemplateSize($pdf->importPage($halaman));
            $w = (float) $data['lebar_mm'];
            $h = $w * $meta[1] / max(1, $meta[0]);
            abort_unless((float) $data['x_mm'] + $w <= $page['width'] + .01 &&
                (float) $data['y_mm'] + $h <= $page['height'] + .01,
                422, 'Gambar tanda tangan keluar dari batas halaman. Atur posisi dan ukuran lagi.');
            DB::transaction(function () use ($request, $suratKeluar, $data, $pathGambar) {
                $surat = SuratKeluar::query()->lockForUpdate()->findOrFail($suratKeluar->id);
                $this->periksaAkses($request, $surat, true);
                DB::table('tanda_tangan_surat_penempatans')->updateOrInsert(
                    ['surat_keluar_id' => $surat->id, 'user_id' => $request->user()->id],
                    [
                        'gambar_path' => $pathGambar, 'halaman' => $data['halaman'],
                        'x_mm' => $data['x_mm'], 'y_mm' => $data['y_mm'],
                        'lebar_mm' => $data['lebar_mm'], 'dikonfirmasi_pada' => now(),
                        'updated_at' => now(), 'created_at' => now(),
                    ]
                );
            }, 3);
        } catch (\Throwable $e) {
            if ($newPath) {
                $disk->delete($newPath);
            }
            throw $e;
        }

        // Dokumen asli tidak diubah. File tanda tangan lama tidak dihapus sebagai cadangan audit.
        return redirect()->route('tanda-tangan.surat.editor', $suratKeluar)
            ->with('success', 'Gambar dan posisi tanda tangan Anda sudah tersimpan. TTD baru ditempel setelah semua persetujuan lengkap.');
    }
}
