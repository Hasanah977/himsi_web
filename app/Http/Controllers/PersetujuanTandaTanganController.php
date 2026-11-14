<?php

namespace App\Http\Controllers;

use App\Models\PersetujuanTandaTanganSurat;
use App\Models\SuratKeluar;
use App\Models\TandaTangan;
use App\Services\NotifikasiSuratService;
use App\Services\PengesahanPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersetujuanTandaTanganController extends Controller
{
    private function authorizeSurat(Request $request, SuratKeluar $surat): void
    {
        abort_unless($request->user() && (int) $surat->penandatangan_pendamping_id === (int) $request->user()->id, 403);
        abort_unless($surat->status === 'disetujui', 403, 'Tunggu persetujuan akhir Ketua Umum.');
    }

    public function index(Request $request)
    {
        $permintaan = PersetujuanTandaTanganSurat::where('user_id', $request->user()->id)
            ->whereHas('suratKeluar', fn ($q) => $q->where('status', 'disetujui'))
            ->with('suratKeluar')->latest()->paginate(10);

        return view('tanda-tangan.permintaan', compact('permintaan'));
    }

    public function show(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeSurat($request, $suratKeluar);
        $izin = PersetujuanTandaTanganSurat::where('surat_keluar_id', $suratKeluar->id)
            ->where('user_id', $request->user()->id)->firstOrFail();
        $tandaTangan = TandaTangan::where('user_id', $request->user()->id)->first();

        return view('tanda-tangan.persetujuan', compact('suratKeluar', 'izin', 'tandaTangan'));
    }

    public function simpanGambar(Request $request)
    {
        $request->validate(['gambar' => 'required|file|mimes:png|max:512']);
        $file = $request->file('gambar');
        abort_unless((@getimagesize($file->getRealPath())[2] ?? null) === IMAGETYPE_PNG, 422, 'Format harus PNG.');
        $disk = Storage::disk('local');
        $path = $file->storeAs('tanda-tangan', $request->user()->id.'-'.Str::uuid().'.png', 'local');
        abort_unless($path, 500, 'Gagal menyimpan tanda tangan.');
        try {
            $lama = TandaTangan::where('user_id', $request->user()->id)->value('file_path');
            TandaTangan::updateOrCreate(['user_id' => $request->user()->id], ['file_path' => $path]);
            // Jangan hapus gambar lama yang mungkin diperlukan sebagai bukti sebelumnya.
        } catch (\Throwable $e) {
            $disk->delete($path);
            throw $e;
        }

        return back()->with('success', 'Gambar tanda tangan tersimpan secara privat.');
    }

    public function putuskan(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeSurat($request, $suratKeluar);
        $data = $request->validate([
            'keputusan' => 'required|in:disetujui,ditolak',
            'konfirmasi' => 'accepted_if:keputusan,disetujui',
            'catatan' => 'required_if:keputusan,ditolak|nullable|string|max:2000',
        ]);
        if ($data['keputusan'] === 'disetujui'
            && strtolower(pathinfo($suratKeluar->file_surat ?? '', PATHINFO_EXTENSION)) === 'pdf'
            && ! DB::table('tanda_tangan_surat_penempatans')
                ->where('surat_keluar_id', $suratKeluar->id)
                ->where('user_id', $request->user()->id)->exists()) {
            return back()->withErrors(['ttd' => 'Upload gambar dan atur posisi tanda tangan Anda sendiri sebelum menyetujui.'])->withInput();
        }
        DB::transaction(function () use ($request, $suratKeluar, $data) {
            $izin = PersetujuanTandaTanganSurat::where('surat_keluar_id', $suratKeluar->id)
                ->where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($izin->status === 'menunggu', 409, 'Permintaan sudah diputuskan.');
            $izin->update(['status' => $data['keputusan'], 'catatan' => $data['catatan'] ?? null, 'diputuskan_pada' => now()]);
        }, 3);
        NotifikasiSuratService::sekretarisKeputusan($suratKeluar,
            $data['keputusan'] === 'disetujui' ? 'mendapat persetujuan tanda tangan pendamping' : 'ditolak penandatangan pendamping');
        if ($data['keputusan'] === 'disetujui') {
            try {
                app(PengesahanPdfService::class)->terbitkan($suratKeluar);
            } catch (\Throwable $e) {
                Log::warning('Pengesahan PDF tertunda: '.$e->getMessage(), ['surat_id' => $suratKeluar->id]);
            }
        }

        return redirect()->route('tanda-tangan.persetujuan.show', $suratKeluar)
            ->with('success', 'Keputusan Anda telah dicatat.');
    }

    public function gambar(Request $request)
    {
        $t = TandaTangan::where('user_id', $request->user()->id)->firstOrFail();
        abort_unless(str_starts_with($t->file_path, 'tanda-tangan/') && Storage::disk('local')->exists($t->file_path), 404);

        return response()->file(Storage::disk('local')->path($t->file_path), [
            'Content-Type' => 'image/png', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
