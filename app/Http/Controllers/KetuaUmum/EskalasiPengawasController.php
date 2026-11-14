<?php

namespace App\Http\Controllers\KetuaUmum;

use App\Http\Controllers\Controller;
use App\Models\NotifikasiHimsi;
use App\Models\PengawasanEskalasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EskalasiPengawasController extends Controller
{
    public function index()
    {
        $eskalasis = PengawasanEskalasi::with(['laporan', 'pengaju'])->latest()->paginate(12);

        return view('ketua-umum.eskalasi-pengawas', compact('eskalasis'));
    }

    public function putuskan(Request $request, PengawasanEskalasi $eskalasi)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['ditindaklanjuti', 'selesai'])],
            'keputusan' => 'required|string|max:6000',
        ]);
        DB::transaction(function () use ($request, $eskalasi, $data) {
            $locked = PengawasanEskalasi::whereKey($eskalasi->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'menunggu', 409, 'Eskalasi ini sudah diputuskan.');
            $locked->update($data + ['diputuskan_oleh' => $request->user()->id, 'diputuskan_pada' => now()]);
            NotifikasiHimsi::kirim((int) $locked->diajukan_oleh, 'Keputusan eskalasi',
                'Ketua Umum telah merespons eskalasi pengawasan.', route('pengawas.eskalasi.index', [], false), 'pengawasan');
        });

        return back()->with('success', 'Keputusan eskalasi berhasil dicatat.');
    }
}
