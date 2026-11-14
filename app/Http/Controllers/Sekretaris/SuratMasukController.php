<?php

namespace App\Http\Controllers\Sekretaris;

use App\Http\Controllers\Controller;
use App\Models\SuratMasuk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuratMasukController extends Controller
{
    private function authorizeSekretaris(Request $request): void
    {
        abort_unless(
            $request->user()?->administration_role === 'Sekertaris',
            403,
            'Anda tidak memiliki akses ke Surat Masuk.'
        );
    }

    private function validateSurat(Request $request, ?SuratMasuk $suratMasuk = null): array
    {
        return $request->validate([
            'nomor_surat' => [
                'required',
                'string',
                'max:100',
                Rule::unique('surat_masuks', 'nomor_surat')
                    ->ignore($suratMasuk?->id),
            ],
            'pengirim' => ['required', 'string', 'max:150'],
            'alamat_pengirim' => ['required', 'string', 'max:255'],
            'perihal' => ['required', 'string', 'max:255'],
            'tanggal_surat' => ['required', 'date'],
            'tanggal_diterima' => ['required', 'date'],
            'lampiran' => ['nullable', 'string', 'max:255'],
            'disposisi' => ['nullable', 'string'],
            'bidang_arsip' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'file_surat' => [
                $suratMasuk ? 'nullable' : 'required',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:10240',
            ],
        ]);
    }

    public function index(Request $request): View
    {
        $this->authorizeSekretaris($request);

        $suratMasuks = SuratMasuk::query()
            ->latest('tanggal_diterima')
            ->latest('id')
            ->paginate(10);

        return view('sekretaris.surat-masuk.index', compact('suratMasuks'));
    }

    public function create(Request $request): View
    {
        $this->authorizeSekretaris($request);

        return view('sekretaris.surat-masuk.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSekretaris($request);

        $data = $this->validateSurat($request);

        $file = $request->file('file_surat');

        $data['file_surat'] = $file->store(
            'surat-masuk',
            'local'
        );

        $data['dicatat_oleh'] = $request->user()->id;
        // Surat Masuk hanya menyimpan file asli tanpa konversi.
        $data['file_pratinjau'] = null;
        try {
            SuratMasuk::create($data);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($data['file_surat']);
            throw $exception;
        }

        return redirect()
            ->route('sekretaris.surat-masuk.index')
            ->with('success', 'Surat masuk berhasil dicatat.');
    }

    public function show(Request $request, SuratMasuk $suratMasuk): View
    {
        $this->authorizeSekretaris($request);

        return view(
            'sekretaris.surat-masuk.show',
            compact('suratMasuk')
        );
    }

    public function edit(Request $request, SuratMasuk $suratMasuk): View
    {
        $this->authorizeSekretaris($request);

        return view(
            'sekretaris.surat-masuk.edit',
            compact('suratMasuk')
        );
    }

    public function update(Request $request, SuratMasuk $suratMasuk): RedirectResponse
    {
        $this->authorizeSekretaris($request);

        $data = $this->validateSurat($request, $suratMasuk);
        $oldFile = $suratMasuk->file_surat;
        $oldPreview = $suratMasuk->file_pratinjau;
        $newFile = null;
        $newPreview = null;

        if ($request->hasFile('file_surat')) {
            $newFile = $request->file('file_surat')->store(
                'surat-masuk',
                'local'
            );

            $data['file_surat'] = $newFile;
        } else {
            unset($data['file_surat']);
        }

        try {
            if ($newFile) {
                // Jika berkas diganti, pratinjau lama tidak digunakan lagi.
                $data['file_pratinjau'] = null;
            }
            $suratMasuk->update($data);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_filter([$newFile, $newPreview]));
            throw $exception;
        }

        if ($newFile) {
            Storage::disk('local')->delete(array_filter([$oldFile, $oldPreview]));
        }

        return redirect()
            ->route('sekretaris.surat-masuk.index')
            ->with('success', 'Surat masuk berhasil diperbarui.');
    }

    public function destroy(Request $request, SuratMasuk $suratMasuk): RedirectResponse
    {
        $this->authorizeSekretaris($request);

        $file = $suratMasuk->file_surat;
        $preview = $suratMasuk->file_pratinjau;
        $suratMasuk->delete();
        Storage::disk('local')->delete(array_filter([$file, $preview]));

        return redirect()
            ->route('sekretaris.surat-masuk.index')
            ->with('success', 'Surat masuk berhasil dihapus.');
    }

    public function lihatDokumen(
        Request $request,
        SuratMasuk $suratMasuk
    ) {
        $this->authorizeSekretaris($request);

        abort_unless(
            $suratMasuk->file_surat &&
            Storage::disk('local')->exists($suratMasuk->file_surat),
            404,
            'Dokumen surat tidak ditemukan.'
        );

        $lihat = $suratMasuk->file_surat;
        $ext = strtolower(pathinfo($lihat, PATHINFO_EXTENSION));
        abort_unless(str_starts_with($lihat, 'surat-masuk/')
            && ! str_contains($lihat, '..') && Storage::disk('local')->exists($lihat),
            404, 'Dokumen surat tidak ditemukan.');
        // Browser tidak menjamin pratinjau asli Word: tawarkan unduhan privat.
        if (in_array($ext, ['doc', 'docx'], true)) {
            return Storage::disk('local')->download($lihat, 'surat-masuk-'.$suratMasuk->id.'.'.$ext);
        }
        $path = Storage::disk('local')->path($lihat);

        $mime = mime_content_type($path);

        abort_unless(
            in_array($mime, [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ], true),
            415,
            'Format dokumen tidak didukung.'
        );

        return response()->file($path, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    public function unduhDokumen(
        Request $request,
        SuratMasuk $suratMasuk
    ) {
        $this->authorizeSekretaris($request);

        abort_unless(
            $suratMasuk->file_surat &&
            Storage::disk('local')->exists($suratMasuk->file_surat),
            404,
            'Dokumen surat tidak ditemukan.'
        );

        $extension = pathinfo($suratMasuk->file_surat, PATHINFO_EXTENSION);

        return Storage::disk('local')->download(
            $suratMasuk->file_surat,
            'surat-masuk-'.$suratMasuk->id.'.'.$extension
        );
    }
}
