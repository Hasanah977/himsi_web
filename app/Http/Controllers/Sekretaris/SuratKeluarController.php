<?php

namespace App\Http\Controllers\Sekretaris;

use App\Http\Controllers\Controller;
use App\Models\OpsSubmission;
use App\Models\PersetujuanTandaTanganSurat;
use App\Models\PprkSubmission;
use App\Models\SuratKeluar;
use App\Models\User;
use App\Services\AdministrationBridge;
use App\Services\NotifikasiSuratService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratKeluarController extends Controller
{
    private function authorizeSekretaris(Request $request): void
    {
        abort_unless(
            $request->user()?->administration_role === 'Sekertaris',
            403,
            'Anda tidak memiliki akses ke halaman ini.'
        );
    }

    public function index(Request $request)
    {
        $this->authorizeSekretaris($request);

        $suratKeluars = SuratKeluar::query()
            ->whereNull('diarsipkan_pada')
            ->latest('created_at')
            ->latest('id')
            ->paginate(10);

        $jumlahSuratKeluar = SuratKeluar::whereNull('diarsipkan_pada')->count();

        $jumlahDraft = SuratKeluar::where(
            'status',
            'draft'
        )->count();

        $jumlahMenunggu = SuratKeluar::where(
            'status',
            'menunggu_persetujuan'
        )->count();

        $jumlahDisetujui = SuratKeluar::where(
            'status',
            'disetujui'
        )->count();

        return view('sekretaris.surat-keluar.index', compact(
            'suratKeluars',
            'jumlahSuratKeluar',
            'jumlahDraft',
            'jumlahMenunggu',
            'jumlahDisetujui'
        ));
    }

    public function create(Request $request)
    {
        $this->authorizeSekretaris($request);

        $jenisSurat = [
            'SKP', 'SPM', 'STG', 'SBA', 'SPJ',
            'SRD', 'SPR', 'SPT', 'SUD', 'SPB',
            'SAD', 'SUL', 'SPH', 'SKT', 'SPN',
            'SKA', 'SUS', 'SBL',
        ];

        $kodeLingkup = range('A', 'H');

        $kodePenerbit = [
            'A', 'B', 'C',
            'D1', 'D2', 'D3', 'D4', 'D5', 'D6',
        ];

        $calonPenandatangan = $this->calonPenandatangan();

        return view('sekretaris.surat-keluar.create', compact(
            'jenisSurat',
            'kodeLingkup',
            'kodePenerbit',
            'calonPenandatangan'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeSekretaris($request);

        $data = $request->validate([
            'ops_submission_id' => ['nullable', 'integer', Rule::exists('ops_submissions', 'id')->where('type', 'letter')->where('status', 'pending_secretary'), Rule::unique('surat_keluars', 'ops_submission_id')],
            'kategori_penomoran' => [
                'required',
                'in:keputusan,reguler,kepanitiaan',
            ],
            'jenis_surat' => [
                'required',
                'in:SKP,SPM,STG,SBA,SPJ,SRD,SPR,SPT,SUD,SPB,SAD,SUL,SPH,SKT,SPN,SKA,SUS,SBL',
            ],
            'kode_lingkup' => [
                'nullable',
                'in:A,B,C,D,E,F,G,H',
            ],
            'kode_penerbit' => [
                'nullable',
                'in:A,B,C,D1,D2,D3,D4,D5,D6',
            ],
            'tujuan' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
            'tanggal_surat' => 'required|date',
            'lampiran' => 'nullable|string|max:255',
            'isi_surat' => 'nullable|string',
            'file_surat' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'jenis_pengajuan' => 'required|in:surat_baru,revisi_surat',
            'penandatangan_pendamping_id' => 'nullable|integer|exists:users,id',
            'ttd_posisi_x' => 'nullable|integer|between:10,190',
            'ttd_posisi_y' => 'nullable|integer|between:15,275',
        ]);

        $this->validasiPenandatangan($request, $data);

        $filePath = null;
        $filePratinjau = null;

        if ($request->hasFile('file_surat')) {
            $filePath = $request->file('file_surat')
                ->store('surat-keluar', 'local');
        }

        try {
            // File Word/PDF/gambar disimpan dalam format asli, tanpa konversi.
            SuratKeluar::create([
                'ops_submission_id' => $data['ops_submission_id'] ?? null,
                'jenis_surat' => $data['jenis_surat'],
                'jenis_pengajuan' => $data['jenis_pengajuan'],
                'penandatangan_pendamping_id' => $data['penandatangan_pendamping_id'] ?? null,
                'ttd_posisi_x' => $data['ttd_posisi_x'] ?? 135,
                'ttd_posisi_y' => $data['ttd_posisi_y'] ?? 235,
                'kategori_penomoran' => $data['kategori_penomoran'],
                'kode_lingkup' => $data['kode_lingkup'] ?? null,
                'kode_penerbit' => $data['kode_penerbit'] ?? null,
                'tujuan' => $data['tujuan'],
                'perihal' => $data['perihal'],
                'tanggal_surat' => $data['tanggal_surat'],
                'lampiran' => $data['lampiran'] ?? null,
                'isi_surat' => $data['isi_surat'] ?? null,
                'file_surat' => $filePath,
                'file_pratinjau' => $filePratinjau,
                'status' => 'draft',
                'dibuat_oleh' => $request->user()->id,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')
                ->delete(array_filter([$filePath, $filePratinjau]));

            throw $exception;
        }

        return redirect()
            ->route('sekretaris.surat-keluar.index')
            ->with('success', 'Draft Surat Keluar berhasil disimpan.');
    }

    public function show(
        Request $request,
        SuratKeluar $suratKeluar
    ) {
        $this->authorizeSekretaris($request);

        $suratKeluar->load([
            'pembuat',
            'penyetuju',
            'penandatanganPendamping',
            'persetujuanTandaTangan.user',
        ]);

        $riwayatPersetujuan = DB::table('riwayat_persetujuan_surats')
            ->where('surat_keluar_id', $suratKeluar->id)
            ->orderBy('pengajuan_ke')
            ->get();

        return view(
            'sekretaris.surat-keluar.show',
            compact('suratKeluar', 'riwayatPersetujuan')
        );
    }

    public function edit(
        Request $request,
        SuratKeluar $suratKeluar
    ) {
        $this->authorizeSekretaris($request);

        abort_unless(
            in_array($suratKeluar->status, ['draft', 'ditolak'], true),
            403,
            'Hanya surat berstatus Draft atau Ditolak yang dapat diedit.'
        );

        $jenisSurat = [
            'SKP', 'SPM', 'STG', 'SBA', 'SPJ',
            'SRD', 'SPR', 'SPT', 'SUD', 'SPB',
            'SAD', 'SUL', 'SPH', 'SKT', 'SPN',
            'SKA', 'SUS', 'SBL',
        ];

        $kodeLingkup = range('A', 'H');

        $kodePenerbit = [
            'A', 'B', 'C',
            'D1', 'D2', 'D3', 'D4', 'D5', 'D6',
        ];

        $calonPenandatangan = $this->calonPenandatangan();

        return view(
            'sekretaris.surat-keluar.edit',
            compact(
                'suratKeluar',
                'jenisSurat',
                'kodeLingkup',
                'kodePenerbit',
                'calonPenandatangan'
            )
        );
    }

    public function update(
        Request $request,
        SuratKeluar $suratKeluar
    ) {
        $this->authorizeSekretaris($request);

        abort_unless(
            in_array($suratKeluar->status, ['draft', 'ditolak'], true),
            403,
            'Hanya surat berstatus Draft atau Ditolak yang dapat diperbarui.'
        );

        $data = $request->validate([
            'kategori_penomoran' => [
                'required',
                'in:keputusan,reguler,kepanitiaan',
            ],
            'jenis_surat' => [
                'required',
                'in:SKP,SPM,STG,SBA,SPJ,SRD,SPR,SPT,SUD,SPB,SAD,SUL,SPH,SKT,SPN,SKA,SUS,SBL',
            ],
            'kode_lingkup' => 'nullable|in:A,B,C,D,E,F,G,H',
            'kode_penerbit' => 'nullable|in:A,B,C,D1,D2,D3,D4,D5,D6',
            'tujuan' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
            'tanggal_surat' => 'required|date',
            'lampiran' => 'nullable|string|max:255',
            'isi_surat' => 'nullable|string',
            'file_surat' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'jenis_pengajuan' => 'required|in:surat_baru,revisi_surat',
            'penandatangan_pendamping_id' => 'nullable|integer|exists:users,id',
            'ttd_posisi_x' => 'nullable|integer|between:10,190',
            'ttd_posisi_y' => 'nullable|integer|between:15,275',
        ]);

        $this->validasiPenandatangan($request, $data);

        $fileLama = $suratKeluar->file_surat;
        $pratinjauLama = $suratKeluar->file_pratinjau;
        $fileBaru = null;
        $pratinjauBaru = null;

        if ($request->hasFile('file_surat')) {
            $fileBaru = $request->file('file_surat')
                ->store('surat-keluar', 'local');
        }

        try {
            // Penggantian berkas tetap menyimpan file asli; tidak menjalankan konversi.
            $dataUpdate = [
                'jenis_pengajuan' => $data['jenis_pengajuan'],
                'penandatangan_pendamping_id' => $data['penandatangan_pendamping_id'] ?? null,
                'ttd_posisi_x' => $data['ttd_posisi_x'] ?? 135,
                'ttd_posisi_y' => $data['ttd_posisi_y'] ?? 235,
                'jenis_surat' => $data['jenis_surat'],
                'kategori_penomoran' => $data['kategori_penomoran'],
                'kode_lingkup' => $data['kode_lingkup'] ?? null,
                'kode_penerbit' => $data['kode_penerbit'] ?? null,
                'tujuan' => $data['tujuan'],
                'perihal' => $data['perihal'],
                'tanggal_surat' => $data['tanggal_surat'],
                'lampiran' => $data['lampiran'] ?? null,
                'isi_surat' => $data['isi_surat'] ?? null,
            ];

            if ($fileBaru !== null) {
                $dataUpdate['file_surat'] = $fileBaru;
                $dataUpdate['file_pratinjau'] = $pratinjauBaru;
                $dataUpdate['ttd_halaman'] = null;
                $dataUpdate['ttd_halaman_pendamping'] = null;
            }

            $updated = SuratKeluar::query()
                ->whereKey($suratKeluar->getKey())
                ->whereIn('status', ['draft', 'ditolak'])
                ->update($dataUpdate);

            abort_unless(
                $updated === 1,
                409,
                'Status surat telah berubah. Draft tidak dapat diperbarui.'
            );
        } catch (\Throwable $exception) {
            Storage::disk('local')
                ->delete(array_filter([$fileBaru, $pratinjauBaru]));

            throw $exception;
        }

        if ($fileBaru !== null) {
            Storage::disk('local')
                ->delete(array_filter([$fileLama, $pratinjauLama]));
        }

        return redirect()
            ->route('sekretaris.surat-keluar.show', $suratKeluar)
            ->with('success', 'Draft Surat Keluar berhasil diperbarui.');
    }

    public function ajukanPersetujuan(
        Request $request,
        SuratKeluar $suratKeluar
    ) {
        $this->authorizeSekretaris($request);

        abort_unless(
            in_array($suratKeluar->status, ['draft', 'ditolak'], true),
            403,
            'Hanya surat berstatus Draft atau Ditolak yang dapat diajukan.'
        );

        if ($suratKeluar->ops_submission_id && strtolower(pathinfo($suratKeluar->file_surat ?? '', PATHINFO_EXTENSION)) !== 'pdf') {
            throw ValidationException::withMessages(['file_surat' => 'Surat program departemen membutuhkan PDF untuk pengesahan tanda tangan. Unggah PDF melalui Edit Surat.']);
        }
        // Semua format yang diterima dapat diajukan untuk persetujuan tanpa konversi.
        $fileAsli = $suratKeluar->file_surat;
        $ext = strtolower(pathinfo($fileAsli ?? '', PATHINFO_EXTENSION));
        abort_unless($fileAsli
            && in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], true)
            && Storage::disk('local')->exists($fileAsli),
            422, 'Unggah dokumen Word, PDF, atau gambar terlebih dahulu sebelum mengajukan persetujuan.');

        $updated = SuratKeluar::query()
            ->whereKey($suratKeluar->getKey())
            ->whereIn('status', ['draft', 'ditolak'])
            ->update([
                'status' => 'menunggu_persetujuan',
                'diajukan_pada' => now(),
                'catatan_persetujuan' => null,
                'diputuskan_pada' => null,
                'disetujui_oleh' => null,
            ]);

        abort_unless(
            $updated === 1,
            409,
            'Status surat telah berubah. Silakan muat ulang halaman.'
        );

        // Pengajuan ulang wajib mendapatkan gambar dan persetujuan pejabat yang baru.
        DB::table('tanda_tangan_surat_penempatans')
            ->where('surat_keluar_id', $suratKeluar->id)->delete();
        $suratKeluar->refresh();
        AdministrationBridge::sync($suratKeluar);
        if ($suratKeluar->penandatangan_pendamping_id) {
            PersetujuanTandaTanganSurat::updateOrCreate(
                ['surat_keluar_id' => $suratKeluar->id, 'user_id' => $suratKeluar->penandatangan_pendamping_id],
                ['status' => 'menunggu', 'catatan' => null, 'diputuskan_pada' => null]
            );
        }
        NotifikasiSuratService::ketuaMenunggu($suratKeluar);

        return redirect()
            ->route('sekretaris.surat-keluar.show', $suratKeluar)
            ->with(
                'success',
                'Surat berhasil diajukan dan sedang menunggu persetujuan Ketua Umum.'
            );
    }

    /** Sekertaris dapat mengatur posisi sebelum surat diajukan. Setelah diajukan posisi terkunci. */
    public function simpanPosisiTandaTangan(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeSekretaris($request);
        abort_unless(in_array($suratKeluar->status, ['draft', 'ditolak'], true), 403,
            'Posisi tanda tangan hanya dapat diubah pada surat draft atau ditolak.');
        abort_unless($suratKeluar->file_surat
            && strtolower(pathinfo($suratKeluar->file_surat, PATHINFO_EXTENSION)) === 'pdf',
            422, 'Pengaturan posisi tanda tangan hanya tersedia untuk surat PDF asli. Word dan gambar tetap bisa diajukan tanpa konversi.');
        $data = $request->validate([
            'ttd_halaman' => 'required|integer|min:1|max:1000',
            'ttd_posisi_x' => 'required|numeric|min:0|max:2000',
            'ttd_posisi_y' => 'required|numeric|min:0|max:2000',
            'ttd_lebar' => 'required|integer|min:15|max:80',
            'ttd_halaman_pendamping' => 'nullable|required_with:ttd_posisi_x_pendamping|integer|min:1|max:1000',
            'ttd_posisi_x_pendamping' => 'nullable|numeric|min:0|max:2000',
            'ttd_posisi_y_pendamping' => 'nullable|numeric|min:0|max:2000',
            'ttd_lebar_pendamping' => 'nullable|integer|min:15|max:80',
        ]);
        // Batas kertas dan nomor halaman diverifikasi ulang oleh service PDF saat diterbitkan.
        if (! $suratKeluar->penandatangan_pendamping_id) {
            $data['ttd_halaman_pendamping'] = null;
            $data['ttd_posisi_x_pendamping'] = null;
            $data['ttd_posisi_y_pendamping'] = null;
            $data['ttd_lebar_pendamping'] = null;
        }
        $updated = SuratKeluar::query()->whereKey($suratKeluar->id)
            ->whereIn('status', ['draft', 'ditolak'])->update($data);
        abort_unless($updated === 1, 409, 'Status surat berubah. Muat ulang halaman.');

        return back()->with('success', 'Posisi tanda tangan tersimpan dan akan terkunci setelah pengajuan.');
    }

    private function calonPenandatangan()
    {
        return User::query()->with([])
            ->whereIn('role', ['sekretaris_umum', 'bendahara_umum', 'koordinator_departemen'])->orderBy('name')->get();
    }

    private function validasiPenandatangan(Request $request, array $data): void
    {
        $id = $data['penandatangan_pendamping_id'] ?? null;
        if (! $id) {
            return;
        }
        $user = User::with([])->findOrFail($id);
        abort_unless(
            in_array($user->administration_role, ['Sekertaris', 'Bendahara', 'Koordinator Departemen'], true),
            422, 'Penandatangan pendamping tidak diperbolehkan.'
        );
    }

    public function pengajuanTandaTangan(Request $request)
    {
        $this->authorizeSekretaris($request);
        $suratKeluars = SuratKeluar::query()->whereNotNull('diajukan_pada')
            ->with(['penandatanganPendamping', 'persetujuanTandaTangan'])
            ->latest('diajukan_pada')->paginate(12);

        return view('sekretaris.surat-keluar.pengajuan-tanda-tangan', compact('suratKeluars'));
    }

    public function arsip(Request $request)
    {
        $this->authorizeSekretaris($request);
        $request->validate(['tab' => ['nullable', 'in:surat,lpj,pprk,rpk']]);
        $tab = $request->input('tab', 'surat');
        $suratKeluars = SuratKeluar::query()->whereNotNull('diarsipkan_pada')
            ->latest('diarsipkan_pada')->paginate(12)->withQueryString();
        $reports = in_array($tab, ['lpj', 'rpk'], true) ? OpsSubmission::with('program')->where('type', $tab === 'rpk' ? 'rpk' : 'report')->where('status', 'archived')->latest()->paginate(12)->withQueryString() : null;
        $pprks = $tab === 'pprk' ? PprkSubmission::where('status', 'archived')->latest('archived_at')->paginate(12)->withQueryString() : null;

        return view('sekretaris.surat-keluar.arsip', compact('suratKeluars', 'reports', 'pprks', 'tab'));
    }

    public function arsipkan(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeSekretaris($request);
        abort_unless($suratKeluar->status === 'disetujui'
            && ($suratKeluar->file_final || $suratKeluar->file_surat), 422,
            'Hanya surat yang sudah disetujui dan memiliki dokumen yang dapat diarsipkan.');
        abort_if($suratKeluar->diarsipkan_pada, 409, 'Surat sudah diarsipkan.');
        $suratKeluar->update(['diarsipkan_pada' => now()]);

        return redirect()->route('sekretaris.arsip-surat.index')->with('success', 'Surat berhasil diarsipkan.');
    }

    public function pulihkanArsip(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeSekretaris($request);
        abort_unless($suratKeluar->diarsipkan_pada, 409, 'Surat tidak berada dalam arsip.');
        $suratKeluar->update(['diarsipkan_pada' => null]);

        return redirect()->route('sekretaris.surat-keluar.show', $suratKeluar)->with('success', 'Surat dikeluarkan dari arsip.');
    }
}
