<?php

namespace App\Http\Controllers\KetuaUmum;

use App\Http\Controllers\Controller;
use App\Models\TandaTangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TandaTanganController extends Controller
{
    public function index(): View
    {
        abort_unless(
            auth()->user()?->administration_role === 'Ketua Himpunan',
            403
        );

        $tandaTangan = TandaTangan::where(
            'user_id',
            auth()->id()
        )->first();

        return view('ketua-umum.tanda-tangan.index', [
            'tandaTangan' => $tandaTangan,
        ]);
    }

    public function simpan(Request $request)
    {
        abort_unless(
            $request->user()?->administration_role === 'Ketua Himpunan',
            403
        );

        $data = $request->validate([
            'tanda_tangan' => ['required', 'string', 'max:300000'],
        ]);

        $gambar = $data['tanda_tangan'];

        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $gambar, $matches)) {
            return back()->withErrors([
                'tanda_tangan' => 'Format tanda tangan tidak valid.',
            ]);
        }

        $binary = base64_decode($matches[1], true);

        if (
            $binary === false ||
            strlen($binary) > 200000 ||
            strlen($binary) < 100 ||
            ! str_starts_with($binary, "\x89PNG\r\n\x1a\n") ||
            @getimagesizefromstring($binary) === false
        ) {
            return back()->withErrors([
                'tanda_tangan' => 'Gambar tanda tangan tidak valid atau terlalu besar.',
            ]);
        }

        $userId = $request->user()->id;

        $namaFile = 'tanda-tangan/'.
            $userId.'-'.Str::uuid().'.png';

        $disk = Storage::disk('local');

        if (! $disk->put($namaFile, $binary)) {
            return back()->with('error', 'Gagal menyimpan file tanda tangan.');
        }

        try {
            $tandaTangan = TandaTangan::where('user_id', $userId)->first();

            $fileLama = $tandaTangan?->file_path;

            TandaTangan::updateOrCreate(
                ['user_id' => $userId],
                ['file_path' => $namaFile]
            );

            if ($fileLama && $fileLama !== $namaFile) {
                $disk->delete($fileLama);
            }
        } catch (\Throwable $e) {
            $disk->delete($namaFile);
            throw $e;
        }

        return redirect()
            ->route('ketua-umum.tanda-tangan')
            ->with('success', 'Tanda tangan berhasil disimpan.');
    }
}
