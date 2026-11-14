<?php

namespace App\Http\Controllers;

use App\Models\KbhIuranPembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuktiIuranController extends Controller
{
    private function dapatMelihat(Request $request, KbhIuranPembayaran $pembayaran): void
    {
        $role = $request->user()?->administration_role;
        abort_unless(
            (in_array($request->user()->role, config('finance.dues_roles'), true) && (int) $pembayaran->user_id === (int) $request->user()->id)
            || $role === 'Bendahara',
            403
        );
        abort_unless($pembayaran->bukti_path && Storage::disk('local')->exists($pembayaran->bukti_path), 404);
    }

    public function lihat(Request $request, KbhIuranPembayaran $pembayaran)
    {
        $this->dapatMelihat($request, $pembayaran);
        $mime = $pembayaran->bukti_mime;
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true), 415);

        return response()->file(Storage::disk('local')->path($pembayaran->bukti_path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="bukti-iuran-'.$pembayaran->id.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function unduh(Request $request, KbhIuranPembayaran $pembayaran)
    {
        $this->dapatMelihat($request, $pembayaran);
        $ekstensi = match ($pembayaran->bukti_mime) {
            'application/pdf' => '.pdf', 'image/png' => '.png', default => '.jpg',
        };

        return Storage::disk('local')->download(
            $pembayaran->bukti_path,
            'bukti-iuran-'.$pembayaran->id.$ekstensi
        );
    }
}
