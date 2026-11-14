<?php

namespace App\Http\Controllers\Sekretaris;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use App\Services\PengesahanPdfService;
use Illuminate\Http\Request;

class DokumenFinalSuratController extends Controller
{
    /** Kompatibilitas route lama: PDF final diterbitkan dari PDF asli dan persetujuan eksplisit. */
    public function simpan(Request $request, SuratKeluar $suratKeluar, PengesahanPdfService $service)
    {
        abort_unless($request->user()?->administration_role === 'Sekertaris', 403);
        $request->validate(['konfirmasi_pengesahan' => 'required|accepted']);
        $service->terbitkan($suratKeluar);

        return redirect()->route('sekretaris.surat-keluar.show', $suratKeluar)
            ->with('success', 'PDF final telah diterbitkan otomatis dengan tanda tangan yang disetujui.');
    }
}
