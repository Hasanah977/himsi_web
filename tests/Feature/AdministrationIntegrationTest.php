<?php

use App\Models\KbhAspirasi;
use App\Models\KbhIuranPembayaran;
use App\Models\KbhPendaftaranSeleksi;
use App\Models\KbhPeriode;
use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\PengawasanEskalasi;
use App\Models\PengawasanLaporan;
use App\Models\PengawasanRekomendasi;
use App\Models\PublicContent;
use App\Models\SuratKeluar;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    $this->withoutVite();
});
it('renders canonical role pages', function (string $role, string $route) {
    $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]))->get(route($route))->assertOk();
})->with([
    ['kbh', 'kbh.dashboard'], ['kbh', 'kbh.program-kerja.index'], ['kbh', 'kbh.pengumuman.index'], ['kbh', 'kbh.voting.index'], ['kbh', 'kbh.voting.hasil'], ['kbh', 'kbh.seleksi.index'], ['kbh', 'kbh.seleksi.hasil'], ['kbh', 'kbh.iuran.index'], ['kbh', 'kbh.aspirasi.index'], ['kbh', 'kbh.profil'], ['kbh', 'kbh.profil.edit'], ['kbh', 'kbh.profil.password'], ['kbh', 'kbh.notifikasi.index'],
    ['ketua_umum', 'ketua-umum.dashboard'], ['ketua_umum', 'ketua-umum.approval-center'], ['ketua_umum', 'ketua-umum.monitoring-organisasi'], ['ketua_umum', 'ketua-umum.persetujuan-surat.index'], ['ketua_umum', 'ketua-umum.tanda-tangan'], ['ketua_umum', 'ketua-umum.eskalasi-pengawas.index'],
    ['sekretaris_umum', 'sekretaris.dashboard'], ['sekretaris_umum', 'sekretaris.surat-masuk.index'], ['sekretaris_umum', 'sekretaris.surat-masuk.create'], ['sekretaris_umum', 'sekretaris.surat-keluar.index'], ['sekretaris_umum', 'sekretaris.surat-keluar.create'], ['sekretaris_umum', 'sekretaris.arsip-surat.index'],
    ['koordinator_pengawas', 'pengawas.dashboard'], ['koordinator_pengawas', 'pengawas.laporan.index'], ['koordinator_pengawas', 'pengawas.laporan.create'], ['koordinator_pengawas', 'pengawas.aktif.index'], ['koordinator_pengawas', 'pengawas.audit.index'], ['koordinator_pengawas', 'pengawas.rekomendasi.index'], ['koordinator_pengawas', 'pengawas.eskalasi.index'], ['koordinator_pengawas', 'administration.programs'], ['bendahara_umum', 'bendahara.iuran.index'],
    ['ketua_umum', 'tanda-tangan.permintaan'], ['super_admin', 'sekretaris.dashboard'], ['super_admin', 'ketua-umum.dashboard'], ['super_admin', 'pengawas.dashboard'], ['super_admin', 'admin.dashboard'],
]);

function administrationUser(string $role): User
{
    return User::factory()->create(['role' => $role, 'is_active' => true]);
}

it('prevents cross-role actions and super admin signing as another officer', function () {
    $this->actingAs(administrationUser('kbh'))->get(route('sekretaris.surat-keluar.index'))->assertForbidden();
    $this->post(route('pengawas.laporan.store'), [])->assertForbidden();
    $this->actingAs(administrationUser('super_admin'))->post(route('sekretaris.surat-keluar.store'), [])->assertForbidden();
});
it('redirects canonical roles to their dashboard', function (string $role, string $route) {
    $this->actingAs(administrationUser($role))->get(route('dashboard'))->assertRedirect(route($route));
})->with([['kbh', 'kbh.dashboard'], ['ketua_umum', 'ketua-umum.dashboard'], ['sekretaris_umum', 'sekretaris.dashboard'], ['koordinator_pengawas', 'pengawas.dashboard']]);
it('shares published public content without exposing drafts', function () {
    PublicContent::create(['section' => 'announcements', 'slug' => 'berita', 'is_published' => true, 'position' => 0, 'payload' => ['title' => 'Informasi HIMSI', 'summary' => 'Informasi resmi']]);
    PublicContent::create(['section' => 'announcements', 'slug' => 'rahasia', 'is_published' => false, 'position' => 0, 'payload' => ['title' => 'Draft rahasia']]);
    $this->actingAs(administrationUser('kbh'))->get(route('kbh.pengumuman.index'))->assertSee('Informasi HIMSI')->assertDontSee('Draft rahasia');
    $this->get(route('kbh.pengumuman.show', 'rahasia'))->assertNotFound();
});
it('uses the existing KPUM ballot and prevents duplicate votes and early results', function () {
    $user = administrationUser('kbh');
    KpumElection::updateOrCreate(['id' => 1], ['title' => 'Pemilihan HIMSI', 'status' => 'open', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
    $c = KpumCandidate::create(['election_id' => 1, 'number' => 1, 'name' => 'Kandidat Satu', 'nim' => '123', 'angkatan' => 2024, 'ipk' => 3.5, 'vision' => 'Visi', 'mission' => 'Misi', 'status' => 'approved']);
    DB::table('kpum_electors')->insert(['election_id' => 1, 'user_id' => $user->id, 'has_voted' => false]);
    $this->actingAs($user)->post(route('kbh.voting.vote'), ['candidate_id' => $c->id, 'confirmed' => 1])->assertRedirect(route('kbh.voting.hasil'));
    $this->assertDatabaseCount('kpum_ballots', 1);
    $this->post(route('kbh.voting.vote'), ['candidate_id' => $c->id, 'confirmed' => 1])->assertSessionHasErrors('vote');
    $this->get(route('kbh.voting.hasil'))->assertSee('Kandidat Satu')->assertDontSee('1 suara')->assertDontSee('Pilih Kandidat');
});
it('shares selection applications and secretary results without self assigning roles', function () {
    Storage::fake('local');
    $this->freezeTime();
    $this->actingAs(administrationUser('super_admin'))->post(route('administration.selection.period'), ['nama' => 'Seleksi 2026', 'mulai_pada' => now()->subDay()->toDateTimeString(), 'selesai_pada' => now()->addDay()->toDateTimeString()])->assertForbidden();
    KbhPeriode::factory()->create(['batch' => null]);
    $user = administrationUser('kbh');
    $this->actingAs($user)->post(route('kbh.seleksi.store'), ['khs' => UploadedFile::fake()->create('khs.pdf', 20, 'application/pdf')])->assertSessionHasNoErrors();
    $record = KbhPendaftaranSeleksi::firstOrFail();
    $this->actingAs(administrationUser('sekretaris_umum'))->post(route('administration.selection.review', $record), ['status' => 'lolos', 'catatan' => 'Lolos administrasi'])->assertSessionHasNoErrors();
    $this->actingAs($user)->get(route('kbh.seleksi.hasil'))->assertSee('Lolos administrasi');
    expect($user->fresh()->role)->toBe('kbh');
    Storage::disk('local')->assertExists($record->khs_path);
});
it('connects oversight recommendations to their department and chair decisions', function () {
    $coordinator = administrationUser('koordinator_departemen');
    $coordinator->forceFill(['department' => 'akademik'])->save();
    $program = OpsProgram::factory()->create(['department' => 'akademik']);
    $watch = administrationUser('koordinator_pengawas');
    $chair = administrationUser('ketua_umum');
    $this->actingAs($watch)->post(route('pengawas.laporan.store'), ['ops_program_id' => $program->id, 'judul' => 'Periksa dokumentasi', 'nama_kegiatan' => 'Kegiatan', 'isi' => 'Dokumentasi perlu dilengkapi'])->assertSessionHasNoErrors();
    $report = PengawasanLaporan::firstOrFail();
    $this->post(route('pengawas.laporan.rekomendasi', $report), ['rekomendasi' => 'Lengkapi dokumen'])->assertSessionHasNoErrors();
    $recommendation = PengawasanRekomendasi::firstOrFail();
    $this->actingAs($coordinator)->get(route('administration.recommendations'))->assertSee('Lengkapi dokumen');
    $this->post(route('administration.recommendations.respond', $recommendation), ['catatan_tindak_lanjut' => 'Dokumen dilengkapi'])->assertSessionHasNoErrors();
    $other = administrationUser('koordinator_departemen');
    $other->forceFill(['department' => 'humas'])->save();
    $this->actingAs($other)->post(route('administration.recommendations.respond', $recommendation), ['catatan_tindak_lanjut' => 'Tidak boleh'])->assertNotFound();
    $this->actingAs($watch)->post(route('pengawas.laporan.eskalasi', $report), ['alasan' => 'Perlu keputusan Ketua'])->assertSessionHasNoErrors();
    $escalation = PengawasanEskalasi::firstOrFail();
    $this->actingAs($chair)->post(route('ketua-umum.eskalasi-pengawas.putuskan', $escalation), ['status' => 'selesai', 'keputusan' => 'Tindak lanjut diperiksa'])->assertSessionHasNoErrors();
    expect($escalation->fresh()->status)->toBe('selesai');
});
it('allows only Advokasi to respond to KBH aspirations', function () {
    $member = administrationUser('kbh');
    $this->actingAs($member)->post(route('kbh.aspirasi.store'), ['judul' => 'Saran kegiatan', 'isi' => 'Tambahkan pelatihan'])->assertSessionHasNoErrors();
    $item = KbhAspirasi::firstOrFail();
    $humas = administrationUser('koordinator_departemen');
    $humas->forceFill(['department' => 'humas'])->save();
    $this->actingAs($humas)->post(route('administration.aspirations.respond', $item), ['status' => 'diproses', 'response' => 'Saran kami tinjau'])->assertSessionHasNoErrors();
    $this->actingAs($member)->get(route('kbh.aspirasi.index'))->assertSee('Saran kami tinjau');
    $this->post(route('administration.aspirations.respond', $item), ['status' => 'selesai', 'response' => 'Tidak berhak'])->assertForbidden();
});
it('shares KBH dues with treasurer and protects private receipts', function () {
    Storage::fake('local');
    $member = administrationUser('kbh');
    $treasurer = administrationUser('bendahara_umum');
    $this->actingAs($member)->post(route('kbh.iuran.store', 1), ['konfirmasi' => 1, 'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 20, 'application/pdf')])->assertSessionHasNoErrors();
    $payment = KbhIuranPembayaran::firstOrFail();
    expect($payment->status)->toBe('menunggu_verifikasi');
    $this->actingAs(administrationUser('kbh'))->get(route('iuran.bukti.unduh', $payment))->assertForbidden();
    $this->actingAs($treasurer)->post(route('bendahara.iuran.putuskan', $payment), ['keputusan' => 'lunas'])->assertSessionHasNoErrors();
    expect($payment->fresh()->status)->toBe('lunas');
    $this->post(route('bendahara.iuran.putuskan', $payment), ['keputusan' => 'ditolak', 'catatan' => 'Tidak boleh ganda'])->assertSessionHasErrors('keputusan');
    Storage::disk('local')->assertExists($payment->bukti_path);
});
it('requires explicit chair consent and links a separately signed PDF to the original departmental program', function () {
    Storage::fake('local');
    $secretary = administrationUser('sekretaris_umum');
    $chair = administrationUser('ketua_umum');
    $program = OpsProgram::factory()->create(['status' => 'pending']);
    $submission = OpsSubmission::factory()->create(['program_id' => $program->id, 'type' => 'letter', 'status' => 'pending_secretary']);
    $pdf = new FPDF;
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 10, 'Surat Program HIMSI');
    $original = $pdf->Output('S');
    $this->actingAs($secretary)->post(route('sekretaris.surat-keluar.store'), [
        'ops_submission_id' => $submission->id, 'kategori_penomoran' => 'reguler', 'jenis_surat' => 'SPM', 'kode_lingkup' => 'A', 'kode_penerbit' => 'B', 'tujuan' => 'Dekan', 'perihal' => 'Permohonan kegiatan', 'tanggal_surat' => now()->toDateString(), 'jenis_pengajuan' => 'surat_baru', 'file_surat' => UploadedFile::fake()->createWithContent('surat.pdf', $original),
    ])->assertSessionHasNoErrors();
    $letter = SuratKeluar::firstOrFail();
    $this->post(route('sekretaris.surat-keluar.ajukan', $letter))->assertSessionHasNoErrors();
    expect($submission->fresh()->status)->toBe('pending_chair');
    $this->post(route('ketua-umum.persetujuan-surat.putuskan', $letter), ['keputusan' => 'disetujui', 'catatan_persetujuan' => 'Setuju', 'konfirmasi_tanda_tangan' => 1])->assertForbidden();
    $this->actingAs($chair)->get(route('tanda-tangan.surat.editor', $letter))->assertOk();
    $this->actingAs($chair)->post(route('tanda-tangan.surat.simpan', $letter), [
        'gambar' => UploadedFile::fake()->createWithContent('ttd.png', file_get_contents(base_path('tests/Fixtures/signature.png'))), 'halaman' => 1, 'x_mm' => 20, 'y_mm' => 60, 'lebar_mm' => 40, 'konfirmasi_gambar' => 1,
    ])->assertSessionHasNoErrors();
    $this->post(route('ketua-umum.persetujuan-surat.putuskan', $letter), ['keputusan' => 'disetujui', 'catatan_persetujuan' => 'Setuju'])->assertSessionHasErrors('konfirmasi_tanda_tangan');
    $this->post(route('ketua-umum.persetujuan-surat.putuskan', $letter), ['keputusan' => 'disetujui', 'catatan_persetujuan' => 'Setuju', 'konfirmasi_tanda_tangan' => 1])->assertSessionHasNoErrors();
    $letter->refresh();
    expect($letter->file_final)->not->toBeNull();
    expect(Storage::disk('local')->get($letter->file_surat))->toBe($original);
    expect($program->fresh()->status)->toBe('approved');
    expect($submission->fresh()->file_path)->toBe($letter->file_final);
    $this->actingAs(administrationUser('kbh'))->get(route('surat-keluar.dokumen-final.unduh', $letter))->assertForbidden();
});
it('shows approved department activities to KBH without revealing drafts or budgets', function () {
    $program = OpsProgram::factory()->create(['status' => 'approved', 'title' => 'Kegiatan Terbuka']);
    $draft = OpsProgram::factory()->create(['status' => 'draft', 'title' => 'Draft Internal']);
    $this->actingAs(administrationUser('kbh'))->get(route('kbh.program-kerja.index'))->assertSee('Kegiatan Terbuka')->assertDontSee('Draft Internal');
    $this->get(route('kbh.activity', $draft))->assertNotFound();
    $this->get(route('kbh.activity', $program))->assertSee('Kegiatan Terbuka')->assertDontSee('Pengajuan');
});
