<?php

namespace App\Http\Controllers\kbh;

use App\Http\Controllers\Controller;
use App\Models\KbhAspirasi;
use App\Models\NotifikasiHimsi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AspirasiController extends Controller
{
    public function index(Request $request)
    {
        $riwayat = KbhAspirasi::where('user_id', $request->user()->id)->latest()->paginate(10);

        return view('kbh.aspirasi.index', compact('riwayat'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'isi' => ['required', 'string', 'max:10000'],
            'lampiran' => ['nullable', 'file', 'mimes:doc,docx,pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $path = null;
        try {
            if ($request->hasFile('lampiran')) {
                $path = $request->file('lampiran')->store('kbh/aspirasi', 'local');
            }
            KbhAspirasi::create([
                'user_id' => $request->user()->id,
                'judul' => $data['judul'],
                'isi' => $data['isi'],
                'lampiran_path' => $path,
                'lampiran_nama' => $request->file('lampiran')?->getClientOriginalName(),
                'lampiran_mime' => $request->file('lampiran')?->getMimeType(),
                'status' => 'terkirim',
            ]);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        User::where('role', 'koordinator_departemen')->where('department', 'humas')->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim((int) $id, 'Aspirasi KBH baru', $data['judul'], route('administration.aspirations', [], false), 'aspirasi'));

        return redirect()->route('kbh.aspirasi.index')->with('success', 'Aspirasi berhasil dikirim dan tersimpan pada riwayat.');
    }

    public function lampiran(Request $request, KbhAspirasi $aspirasi, string $aksi)
    {
        abort_unless($aspirasi->user_id === $request->user()->id, 403);
        abort_unless(in_array($aksi, ['lihat', 'unduh'], true), 404);
        abort_unless($aspirasi->lampiran_path && Storage::disk('local')->exists($aspirasi->lampiran_path), 404);
        $disk = Storage::disk('local');
        $path = $disk->path($aspirasi->lampiran_path);
        $extension = strtolower(pathinfo($aspirasi->lampiran_nama ?? '', PATHINFO_EXTENSION));
        $nama = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $aspirasi->lampiran_nama ?? 'lampiran');
        if ($aksi === 'lihat' && in_array($extension, ['pdf', 'png', 'jpg', 'jpeg'], true)) {
            $mime = match ($extension) {
                'pdf' => 'application/pdf', 'png' => 'image/png', default => 'image/jpeg'
            };

            return response()->file($path, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$nama.'"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }
        if ($aksi === 'lihat') {
            return redirect()->route('kbh.aspirasi.index')->with('info', 'Word dapat dibuka menggunakan Microsoft Word melalui tombol Unduh.');
        }

        return $disk->download($aspirasi->lampiran_path, $nama);
    }
}
