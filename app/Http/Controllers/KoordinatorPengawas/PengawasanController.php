<?php

namespace App\Http\Controllers\KoordinatorPengawas;

use App\Http\Controllers\Controller;
use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\PengawasanAudit;
use App\Models\PengawasanEskalasi;
use App\Models\PengawasanLaporan;
use App\Models\PengawasanRekomendasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PengawasanController extends Controller
{
    public function dashboard()
    {
        $statistik = [
            'laporan' => PengawasanLaporan::count(),
            'aktif' => PengawasanLaporan::whereIn('status', ['masuk', 'proses', 'perlu_perbaikan'])->count(),
            'audit' => PengawasanAudit::count(),
            'perbaikan' => PengawasanRekomendasi::whereIn('status', ['terbuka', 'ditindaklanjuti'])->count(),
            'eskalasi' => PengawasanEskalasi::where('status', 'menunggu')->count(),
        ];
        $terbaru = PengawasanLaporan::latest()->limit(6)->get();

        return view('koordinator-pengawas.dashboard', compact('statistik', 'terbaru'));
    }

    public function laporanMasuk()
    {
        $laporans = PengawasanLaporan::latest()->paginate(12);

        return view('koordinator-pengawas.laporan-masuk', compact('laporans'));
    }

    public function buatLaporan()
    {
        return view('koordinator-pengawas.buat-laporan', ['programs' => OpsProgram::orderBy('title')->get(['id', 'title', 'department'])]);
    }

    public function simpanLaporan(Request $request)
    {
        $data = $request->validate([
            'ops_program_id' => 'nullable|integer|exists:ops_programs,id', 'judul' => 'required|string|max:180', 'nama_kegiatan' => 'required|string|max:180',
            'departemen' => 'nullable|string|max:100', 'isi' => 'required|string|max:10000',
            'lampiran' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);
        $data['dibuat_oleh'] = $request->user()->id;
        $data['sumber'] = 'input_manual';
        if (! empty($data['ops_program_id'])) {
            $program = OpsProgram::findOrFail($data['ops_program_id']);
            $data['nama_kegiatan'] = $program->title;
            $data['departemen'] = $program->department;
            $data['sumber'] = 'program_departemen';
        }
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $data['lampiran_path'] = $file->store('pengawasan/laporan', 'local');
            $data['lampiran_nama'] = $file->getClientOriginalName();
        }
        unset($data['lampiran']);
        $laporan = PengawasanLaporan::create($data);

        return redirect()->route('pengawas.laporan.show', $laporan)->with('success', 'Laporan pengawasan berhasil dicatat.');
    }

    public function detail(PengawasanLaporan $laporan)
    {
        $laporan->load(['pembuat', 'audits.auditor', 'rekomendasis', 'eskalasis']);

        return view('koordinator-pengawas.detail-laporan', compact('laporan'));
    }

    public function lampiran(PengawasanLaporan $laporan, string $aksi)
    {
        abort_unless(in_array($aksi, ['lihat', 'unduh'], true), 404);
        abort_unless($laporan->lampiran_path && Storage::disk('local')->exists($laporan->lampiran_path), 404);
        $path = Storage::disk('local')->path($laporan->lampiran_path);
        if ($aksi === 'lihat') {
            if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                return response('Dokumen Word dapat dibuka menggunakan tombol Unduh Lampiran.', 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
            }

            return response()->file($path, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
        }

        return Storage::disk('local')->download($laporan->lampiran_path, $laporan->lampiran_nama);
    }

    public function pengawasanAktif()
    {
        $laporans = PengawasanLaporan::whereIn('status', ['masuk', 'proses', 'perlu_perbaikan'])->latest()->paginate(12);

        return view('koordinator-pengawas.pengawasan-aktif', compact('laporans'));
    }

    public function simpanAudit(Request $request, PengawasanLaporan $laporan)
    {
        $data = $request->validate([
            'hasil' => ['required', Rule::in(['sesuai', 'perlu_perbaikan', 'temuan'])],
            'catatan' => 'required|string|max:10000',
        ]);
        DB::transaction(function () use ($request, $laporan, $data) {
            $locked = PengawasanLaporan::whereKey($laporan->id)->lockForUpdate()->firstOrFail();
            PengawasanAudit::create(['laporan_id' => $locked->id, 'auditor_id' => $request->user()->id] + $data);
            $locked->update(['status' => match ($data['hasil']) {
                'sesuai' => 'selesai','perlu_perbaikan' => 'perlu_perbaikan','temuan' => 'proses'
            }]);
        });

        return back()->with('success', 'Hasil audit berhasil dicatat.');
    }

    public function hasilAudit()
    {
        $audits = PengawasanAudit::with(['laporan', 'auditor'])->latest()->paginate(15);

        return view('koordinator-pengawas.hasil-audit', compact('audits'));
    }

    public function rekomendasi()
    {
        $rekomendasis = PengawasanRekomendasi::with('laporan')->latest()->paginate(12);

        return view('koordinator-pengawas.rekomendasi', compact('rekomendasis'));
    }

    public function simpanRekomendasi(Request $request, PengawasanLaporan $laporan)
    {
        $data = $request->validate([
            'rekomendasi' => 'required|string|max:6000', 'tenggat' => 'nullable|date|after_or_equal:today',
        ]);
        $data['laporan_id'] = $laporan->id;
        $data['dibuat_oleh'] = $request->user()->id;
        PengawasanRekomendasi::create($data);
        if ($laporan->ops_program_id) {
            $program = OpsProgram::findOrFail($laporan->ops_program_id);
            User::where('role', 'koordinator_departemen')->where('department', $program->department)->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'Rekomendasi Pengawas', $laporan->judul, route('administration.recommendations', [], false), 'pengawasan'));
        }
        $laporan->update(['status' => 'perlu_perbaikan']);

        return back()->with('success', 'Teguran/rekomendasi tercatat.');
    }

    public function tindakLanjut(Request $request, PengawasanRekomendasi $rekomendasi)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['terbuka', 'ditindaklanjuti', 'selesai'])],
            'catatan_tindak_lanjut' => 'required|string|max:6000',
        ]);
        $rekomendasi->update($data);

        return back()->with('success', 'Status tindak lanjut diperbarui.');
    }

    public function eskalasi()
    {
        $eskalasis = PengawasanEskalasi::with(['laporan', 'pengaju'])->latest()->paginate(12);

        return view('koordinator-pengawas.eskalasi', compact('eskalasis'));
    }

    public function simpanEskalasi(Request $request, PengawasanLaporan $laporan)
    {
        $data = $request->validate([
            'rekomendasi_id' => ['nullable', 'integer', Rule::exists('pengawasan_rekomendasis', 'id')->where('laporan_id', $laporan->id)],
            'alasan' => 'required|string|max:6000',
        ]);
        $eskalasi = PengawasanEskalasi::create([
            'laporan_id' => $laporan->id, 'diajukan_oleh' => $request->user()->id,
            'rekomendasi_id' => $data['rekomendasi_id'] ?? null, 'alasan' => $data['alasan'],
        ]);
        $url = route('ketua-umum.eskalasi-pengawas.index', [], false);
        User::where('role', 'ketua_umum')
            ->where('is_active', true)->pluck('id')->each(function ($id) use ($laporan, $url) {
                NotifikasiHimsi::kirim((int) $id, 'Eskalasi pengawasan baru',
                    'Perlu keputusan Ketua Umum: '.$laporan->judul, $url, 'pengawasan');
            });

        return redirect()->route('pengawas.eskalasi.index')->with('success', 'Eskalasi sudah dikirim kepada Ketua Umum.');
    }
}
