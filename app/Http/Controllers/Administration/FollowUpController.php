<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\KbhAspirasi;
use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\PengawasanRekomendasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FollowUpController extends Controller
{
    private function humas(Request $request): void
    {
        abort_unless(($request->user()->role === 'koordinator_departemen' && $request->user()->department === 'humas') || ($request->user()->isSuperAdmin() && $request->isMethodSafe()), 403);
    }

    public function aspirations(Request $request)
    {
        $this->humas($request);

        return view('administration.aspirations', ['items' => KbhAspirasi::with('user')->latest()->paginate(15)]);
    }

    public function respond(Request $request, KbhAspirasi $aspiration)
    {
        $this->humas($request);
        $data = $request->validate(['status' => 'required|in:diproses,selesai', 'response' => 'required|string|max:6000']);
        $aspiration->update($data + ['responded_by' => $request->user()->id]);
        NotifikasiHimsi::kirim($aspiration->user_id, 'Tanggapan aspirasi', 'Humas menanggapi: '.$aspiration->judul, route('kbh.aspirasi.index', [], false), 'aspirasi');

        return back()->with('success', 'Tanggapan dikirim kepada anggota.');
    }

    public function aspirationFile(Request $request, KbhAspirasi $aspiration)
    {
        $this->humas($request);
        abort_unless($aspiration->lampiran_path && Storage::disk('local')->exists($aspiration->lampiran_path), 404);

        return Storage::disk('local')->download($aspiration->lampiran_path, $aspiration->lampiran_nama);
    }

    private function recommendationsQuery(Request $request)
    {
        abort_unless($request->user()->role === 'koordinator_departemen', 403);

        return PengawasanRekomendasi::with('laporan')->whereHas('laporan', fn ($q) => $q->whereIn('ops_program_id', OpsProgram::where('department', $request->user()->department)->select('id')));
    }

    public function recommendations(Request $request)
    {
        return view('administration.recommendations', ['items' => $this->recommendationsQuery($request)->latest()->paginate(15)]);
    }

    public function followUp(Request $request, int $recommendation)
    {
        $item = $this->recommendationsQuery($request)->findOrFail($recommendation);
        abort_if($item->status === 'selesai', 422, 'Rekomendasi sudah ditutup oleh Pengawas.');
        $data = $request->validate(['catatan_tindak_lanjut' => 'required|string|max:6000']);
        $item->update($data + ['status' => 'ditindaklanjuti']);
        NotifikasiHimsi::kirim($item->dibuat_oleh, 'Tindak lanjut departemen', $item->laporan->judul, route('pengawas.laporan.show', $item->laporan_id, false), 'pengawasan');

        return back()->with('success', 'Tindak lanjut dikirim kepada Pengawas.');
    }
}
