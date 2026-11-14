<?php

use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\OpsTask;
use App\Models\PprkSubmission;
use App\Models\SuratKeluar;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');
});

function documentOfficer(string $role = 'koordinator_departemen', string $department = 'kaderisasi'): User
{
    return User::factory()->create(['role' => $role, 'department' => $department, 'is_active' => true]);
}

function documentProgramData(User $user): array
{
    return ['title' => 'Pelatihan Sistem Informasi', 'description' => 'Pelatihan anggota', 'period' => config('department.period'), 'starts_on' => '2026-10-24', 'ends_on' => '2026-10-26', 'location' => 'Kampus', 'target' => 'Mahasiswa', 'pic_id' => $user->id, 'members' => [$user->id], 'version' => 0];
}

it('blocks first login and program creation until the department and active period RPK is approved', function () {
    $user = documentOfficer();
    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('department.pprk'));
    $this->get(route('department.program.create', 'kaderisasi'))->assertRedirect(route('department.pprk'));
    $this->post(route('department.program.store', 'kaderisasi'), documentProgramData($user))->assertSessionHasErrors('workflow');
    PprkSubmission::factory()->approved()->create(['department' => 'humas']);
    PprkSubmission::factory()->approved()->create(['period' => '2025/2026']);
    $this->post(route('department.program.store', 'kaderisasi'), documentProgramData($user))->assertSessionHasErrors('workflow');
    $this->assertDatabaseCount('ops_programs', 0);
    PprkSubmission::factory()->approved()->create();
    $this->get(route('department.program.create', 'kaderisasi'))->assertOk();
    $this->post(route('department.program.store', 'kaderisasi'), documentProgramData($user))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('ops_programs', ['title' => 'Pelatihan Sistem Informasi', 'status' => 'draft']);
});

it('requires three independent RPK reviews then supervisor approval and secretary archival', function () {
    $coordinator = documentOfficer();
    $chair = documentOfficer('ketua_umum');
    $treasurer = documentOfficer('bendahara_umum');
    $secretary = documentOfficer('sekretaris_umum');
    $supervisor = documentOfficer('koordinator_pengawas');
    $this->actingAs($coordinator)->post(route('department.pprk.upload'), ['version' => 0, 'file' => UploadedFile::fake()->create('pprk.pdf', 20, 'application/pdf')])->assertSessionHasNoErrors();
    $pprk = PprkSubmission::firstOrFail();
    Storage::disk('local')->assertExists($pprk->file_path);
    expect(NotifikasiHimsi::whereIn('user_id', [$chair->id, $treasurer->id, $secretary->id])->count())->toBe(3);
    $this->get(route('department.pprk'))->assertOk()->assertSee('Review tiga pengurus');
    $this->actingAs($supervisor)->get(route('department.pprk.file', $pprk))->assertNotFound();
    $this->post(route('department.pprk.review', $pprk), ['version' => 1, 'action' => 'approve', 'note' => 'Setuju'])->assertForbidden();
    foreach ([$chair, $treasurer, $secretary] as $officer) {
        $this->actingAs($officer)->get(route('department.pprk'))->assertOk()->assertSee('Catatan pemeriksaan');
        $this->post(route('department.pprk.review', $pprk), ['version' => $pprk->fresh()->version, 'action' => 'approve', 'note' => 'Sesuai bidang pemeriksaan'])->assertSessionHasNoErrors();
    }
    expect($pprk->fresh()->status)->toBe('pending_supervisor');
    expect(PprkSubmission::approvedFor('kaderisasi', config('department.period')))->toBeFalse();
    $this->actingAs($secretary)->post(route('department.pprk.review', $pprk), ['version' => 4, 'action' => 'archive', 'note' => 'Arsip'])->assertForbidden();
    $this->actingAs($supervisor)->get(route('department.pprk.file', $pprk))->assertOk();
    $this->post(route('department.pprk.review', $pprk), ['version' => 4, 'action' => 'approve', 'note' => 'Layak dilaksanakan'])->assertSessionHasNoErrors();
    expect(PprkSubmission::approvedFor('kaderisasi', config('department.period')))->toBeTrue();
    $this->actingAs($secretary)->get(route('department.pprk'))->assertOk()->assertSee('Arsipkan RPK');
    $this->post(route('department.pprk.review', $pprk), ['version' => 5, 'action' => 'archive', 'note' => 'Disimpan sebagai arsip periode'])->assertSessionHasNoErrors();
    expect($pprk->fresh())->status->toBe('archived')->archived_by->toBe($secretary->id)->archived_at->not->toBeNull();
    $this->actingAs($coordinator)->post(route('department.pprk.upload'), ['version' => 6, 'file' => UploadedFile::fake()->create('baru.pdf', 20, 'application/pdf')])->assertSessionHasErrors('workflow');
});

it('waits for all three reviews after rejection and resets approvals on a new RPK revision', function () {
    $user = documentOfficer();
    $this->actingAs($user)->post(route('department.pprk.upload'), ['version' => 0, 'file' => UploadedFile::fake()->create('pprk.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])->assertSessionHasNoErrors();
    $record = PprkSubmission::firstOrFail();
    foreach (['ketua_umum' => 'reject', 'bendahara_umum' => 'approve', 'sekretaris_umum' => 'approve'] as $role => $decision) {
        $this->actingAs(documentOfficer($role))->post(route('department.pprk.review', $record), ['version' => $record->fresh()->version, 'action' => $decision, 'note' => 'Catatan wajib'])->assertSessionHasNoErrors();
    }
    expect($record->fresh()->status)->toBe('rejected');
    expect($record->fresh()->reviews)->toHaveCount(3);
    $this->actingAs(documentOfficer('koordinator_pengawas'))->get(route('department.pprk'))->assertOk()->assertDontSee('Catatan wajib');
    $this->actingAs($user)->post(route('department.pprk.upload'), ['version' => 1, 'file' => UploadedFile::fake()->create('perbaikan.pdf', 20, 'application/pdf')])->assertSessionHasErrors('version');
    $this->post(route('department.pprk.upload'), ['version' => 4, 'file' => UploadedFile::fake()->create('perbaikan.pdf', 20, 'application/pdf')])->assertSessionHasNoErrors();
    expect($record->fresh())->revision->toBe(2)->reviews->toBe([])->status->toBe('pending_officers');
    expect($record->fresh()->history)->toHaveCount(5);
});

it('protects RPK uploads downloads and decisions across roles and departments', function () {
    $this->get(route('department.pprk'))->assertRedirect(route('login'));
    $this->actingAs(documentOfficer('anggota_departemen'))->get(route('department.pprk'))->assertForbidden();
    $this->post(route('department.pprk.upload'), [])->assertForbidden();
    $record = PprkSubmission::factory()->approved()->create();
    $this->actingAs(documentOfficer('koordinator_departemen', 'humas'))->get(route('department.pprk.file', $record))->assertNotFound();
    $this->post(route('department.pprk.review', $record), ['version' => 5, 'action' => 'approve', 'note' => 'Injected'])->assertForbidden();
    $this->actingAs(documentOfficer())->post(route('department.pprk.upload'), ['version' => 0, 'file' => UploadedFile::fake()->create('file.txt', 5, 'text/plain')])->assertSessionHasErrors('file');
    $this->post(route('department.pprk.upload'), ['version' => 0, 'file' => UploadedFile::fake()->create('file.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('file');
});

it('blocks duplicate RPK reviewer decisions and requires notes', function () {
    $record = PprkSubmission::factory()->create(['status' => 'pending_officers', 'version' => 1]);
    $chair = documentOfficer('ketua_umum');
    $this->actingAs($chair)->post(route('department.pprk.review', $record), ['version' => 1, 'action' => 'approve'])->assertSessionHasErrors('note');
    $this->post(route('department.pprk.review', $record), ['version' => 1, 'action' => 'approve', 'note' => 'Setuju'])->assertSessionHasNoErrors();
    $this->post(route('department.pprk.review', $record), ['version' => 2, 'action' => 'reject', 'note' => 'Ubah keputusan'])->assertSessionHasErrors('workflow');
    expect($record->fresh()->reviews['ketua_umum']['decision'])->toBe('approve');
});

it('allows only uploaded proposals and forwards the same file without generating a letter', function () {
    $program = OpsProgram::factory()->create();
    $coordinator = documentOfficer();
    $url = route('department.submission.save', ['kaderisasi', $program, 'letter']);
    $this->actingAs($coordinator)->post($url, ['version' => 0, 'action' => 'submit', 'body' => 'Text only'])->assertSessionHasErrors('workflow');
    $this->post($url, ['version' => 0, 'action' => 'submit', 'file' => UploadedFile::fake()->create('proposal.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    $submission = OpsSubmission::firstOrFail();
    $original = $submission->file_path;
    $this->actingAs(documentOfficer('sekretaris_umum'))->post(route('department.review.save', $submission), ['version' => 1, 'action' => 'forward', 'note' => 'Proposal lengkap'])->assertSessionHasNoErrors();
    expect($submission->fresh())->status->toBe('pending_chair')->file_path->toBe($original);
    expect(SuratKeluar::count())->toBe(0);
});

it('requires a RAB file and total then preserves treasurer chair and disbursement ordering', function () {
    $program = OpsProgram::factory()->create(['status' => 'approved']);
    $url = route('department.submission.save', ['kaderisasi', $program, 'budget']);
    $this->actingAs(documentOfficer())->post($url, ['version' => 0, 'action' => 'submit', 'total' => 850000])->assertSessionHasErrors('workflow');
    $this->post($url, ['version' => 0, 'action' => 'submit', 'total' => 0, 'file' => UploadedFile::fake()->create('rab.pdf', 10, 'application/pdf')])->assertSessionHasErrors('total');
    $this->post($url, ['version' => 0, 'action' => 'submit', 'total' => 850000, 'file' => UploadedFile::fake()->create('rab.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    $submission = OpsSubmission::firstOrFail();
    expect($submission)->status->toBe('pending_treasurer')->total->toBe(850000);
    $this->actingAs(documentOfficer('ketua_umum'))->post(route('department.review.save', $submission), ['version' => 1, 'action' => 'approve', 'note' => 'Setuju'])->assertForbidden();
    $this->actingAs(documentOfficer('bendahara_umum'))->get(route('finance.funding.show', $submission))->assertOk()->assertSee('rab.pdf');
    $this->post(route('department.review.save', $submission), ['version' => 1, 'action' => 'forward', 'note' => 'Nominal sesuai dokumen'])->assertSessionHasNoErrors();
    $this->actingAs(documentOfficer('ketua_umum'))->post(route('department.review.save', $submission), ['version' => 2, 'action' => 'approve', 'note' => 'Setuju'])->assertSessionHasNoErrors();
    expect($submission->fresh()->status)->toBe('ready_funding');
    $this->actingAs(documentOfficer())->post(route('department.program.transition', ['kaderisasi', $program]), ['version' => 0, 'action' => 'start'])->assertSessionHasErrors('workflow');
});

it('blocks LPJ drafts and submission before all tasks are validated done', function (string $action) {
    $program = OpsProgram::factory()->create(['status' => 'running']);
    OpsTask::factory()->create(['program_id' => $program->id, 'status' => 'done']);
    OpsTask::factory()->create(['program_id' => $program->id, 'status' => 'submitted']);
    $this->actingAs(documentOfficer())->get(route('department.submission.edit', ['kaderisasi', $program, 'report']))->assertOk()->assertSee('Unggah LPJ terkunci');
    $this->post(route('department.submission.save', ['kaderisasi', $program, 'report']), ['version' => 0, 'action' => $action, 'file' => UploadedFile::fake()->create('lpj.pdf', 10, 'application/pdf')])->assertSessionHasErrors('workflow');
    $this->assertDatabaseCount('ops_submissions', 0);
})->with(['draft', 'submit']);

it('archives LPJ only after chair and supervisor approval and automatically completes the program', function () {
    $program = OpsProgram::factory()->create(['status' => 'running']);
    OpsTask::factory()->create(['program_id' => $program->id, 'status' => 'done']);
    $coordinator = documentOfficer();
    $chair = documentOfficer('ketua_umum');
    $supervisor = documentOfficer('koordinator_pengawas');
    $secretary = documentOfficer('sekretaris_umum');
    $this->actingAs($coordinator)->post(route('department.submission.save', ['kaderisasi', $program, 'report']), ['version' => 0, 'action' => 'submit', 'file' => UploadedFile::fake()->create('lpj.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    $submission = OpsSubmission::firstOrFail();
    $this->actingAs($secretary)->post(route('department.review.save', $submission), ['version' => 1, 'action' => 'archive', 'note' => 'Arsip'])->assertForbidden();
    $this->actingAs($chair)->post(route('department.review.save', $submission), ['version' => 1, 'action' => 'approve', 'note' => 'LPJ lengkap'])->assertSessionHasNoErrors();
    expect($submission->fresh()->status)->toBe('pending_supervisor');
    $this->actingAs($coordinator)->post(route('department.program.transition', ['kaderisasi', $program]), ['version' => 0, 'action' => 'finish'])->assertSessionHasErrors('workflow');
    $this->actingAs($supervisor)->get(route('department.review', $submission))->assertOk()->assertSee('Keputusan Pemeriksaan');
    $this->post(route('department.review.save', $submission), ['version' => 2, 'action' => 'approve', 'note' => 'Pemeriksaan selesai'])->assertSessionHasNoErrors();
    expect($program->fresh()->status)->toBe('running');
    $this->actingAs($secretary)->get(route('department.review', $submission))->assertOk()->assertSee('Arsipkan LPJ');
    $this->post(route('department.review.save', $submission), ['version' => 3, 'action' => 'archive', 'note' => 'LPJ diarsipkan'])->assertSessionHasNoErrors();
    expect($program->fresh()->status)->toBe('completed');
    expect($submission->fresh())->status->toBe('archived');
    expect($submission->fresh()->payload['archived_by'])->toBe($secretary->id);
    $this->get(route('department.reviews', ['status' => 'archived']))->assertOk()->assertSee('Diarsipkan');
});

it('provides all three private templates to coordinators', function (string $type, string $path, string $filename) {
    Storage::disk('local')->put($path, 'template');
    $this->actingAs(documentOfficer())->get(route('department.document.template', ['kaderisasi', $type]))->assertDownload($filename);
    $this->actingAs(documentOfficer('koordinator_departemen', 'humas'))->get(route('department.document.template', ['kaderisasi', $type]))->assertForbidden();
})->with([
    ['letter', 'templates/proposal-himsi.pdf', 'Pedoman-Proposal-HIMSI.pdf'],
    ['budget', 'templates/rab-himsi.docx', 'Template-RAB-HIMSI.docx'],
    ['report', 'templates/lpj-himsi.docx', 'Template-LPJ-HIMSI.docx'],
]);

it('accepts an LPJ as large as the supplied Word template and rejects files beyond 25 MB', function () {
    $program = OpsProgram::factory()->create(['status' => 'running']);
    OpsTask::factory()->create(['program_id' => $program->id, 'status' => 'done']);
    $this->actingAs(documentOfficer())->post(route('department.submission.save', ['kaderisasi', $program, 'report']), ['version' => 0, 'action' => 'submit', 'file' => UploadedFile::fake()->create('lpj.docx', 25601, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])->assertSessionHasErrors('file');
    $this->post(route('department.submission.save', ['kaderisasi', $program, 'report']), ['version' => 0, 'action' => 'submit', 'file' => UploadedFile::fake()->create('lpj.docx', 22000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])->assertSessionHasNoErrors();
    expect(OpsSubmission::firstOrFail()->status)->toBe('pending_chair');
});

it('keeps legacy chair approved LPJs available for supervisor verification', function () {
    $program = OpsProgram::factory()->create(['status' => 'running']);
    $submission = OpsSubmission::factory()->create(['program_id' => $program->id, 'type' => 'report', 'status' => 'approved', 'version' => 0]);
    $this->actingAs(documentOfficer('koordinator_pengawas'))->get(route('department.review', $submission))->assertOk()->assertSee('Keputusan Pemeriksaan');
    $this->post(route('department.review.save', $submission), ['version' => $submission->version, 'action' => 'approve', 'note' => 'Diperiksa sesuai alur baru'])->assertSessionHasNoErrors();
    expect($submission->fresh()->status)->toBe('pending_archive');
});

it('sends rejected LPJ revisions back through chair approval instead of retaining supervisor approval', function () {
    $program = OpsProgram::factory()->create(['status' => 'running']);
    OpsTask::factory()->create(['program_id' => $program->id, 'status' => 'done']);
    $submission = OpsSubmission::factory()->create(['program_id' => $program->id, 'type' => 'report', 'status' => 'pending_supervisor', 'version' => 2, 'file_path' => 'old.pdf']);
    $this->actingAs(documentOfficer('koordinator_pengawas'))->post(route('department.review.save', $submission), ['version' => 2, 'action' => 'revision', 'note' => 'Lengkapi dokumentasi'])->assertSessionHasNoErrors();
    $coordinator = documentOfficer();
    $this->actingAs($coordinator)->post(route('department.submission.save', ['kaderisasi', $program, 'report']), ['version' => 3, 'action' => 'submit'])->assertSessionHasErrors('workflow');
    $this->post(route('department.submission.save', ['kaderisasi', $program, 'report']), ['version' => 3, 'action' => 'submit', 'file' => UploadedFile::fake()->create('lpj-revisi.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    expect($submission->fresh()->status)->toBe('pending_chair');
});

it('shows coordinator RPK and compact header navigation in every department', function (string $department) {
    $user = documentOfficer('koordinator_departemen', $department);
    $response = $this->actingAs($user)->get(route('department.dashboard', $department))->assertOk()->assertSee('Pengajuan RPK');
    $html = $response->getContent();
    $sidebar = substr($html, strpos($html, '<aside'), strpos($html, '</aside>') - strpos($html, '<aside'));
    expect($sidebar)->not->toContain('Permintaan Tanda Tangan', 'Rekomendasi Pengawas', '>Notifikasi<');
    expect($html)->toContain('compact-department', 'aria-label="Notifikasi,');
})->with(['humas', 'akademik', 'kaderisasi', 'kesekretariatan', 'advokasi', 'dana_usaha']);

it('uses compact secretary header with notifications outside the sidebar', function () {
    $response = $this->actingAs(documentOfficer('sekretaris_umum'))->get(route('department.pprk'))->assertOk();
    $html = $response->getContent();
    $sidebar = substr($html, strpos($html, '<aside'), strpos($html, '</aside>') - strpos($html, '<aside'));
    expect($sidebar)->toContain('Pengajuan RPK')->not->toContain('>Notifikasi<');
    expect($html)->toContain('compact-administration', 'aria-label="Notifikasi,');
});

it('prevents program creation when the supervisor rejects an otherwise approved RPK', function () {
    $record = PprkSubmission::factory()->create(['status' => 'pending_supervisor', 'version' => 4, 'file_path' => 'pprk.pdf', 'file_name' => 'pprk.pdf']);
    $this->actingAs(documentOfficer('koordinator_pengawas'))->post(route('department.pprk.review', $record), ['version' => 4, 'action' => 'reject', 'note' => 'Jadwal belum sesuai'])->assertSessionHasNoErrors();
    expect($record->fresh()->status)->toBe('supervisor_rejected');
    $coordinator = documentOfficer();
    $this->actingAs($coordinator)->post(route('department.program.store', 'kaderisasi'), documentProgramData($coordinator))->assertSessionHasErrors('workflow');
    $this->get(route('department.pprk'))->assertOk()->assertSee('Unggah perbaikan RPK');
});

it('serves document previews inline without exposing them across departments', function () {
    Storage::disk('local')->put('department/preview.pdf', "%PDF-1.4\n%%EOF");
    $submission = OpsSubmission::factory()->create(['file_path' => 'department/preview.pdf', 'file_name' => 'proposal.pdf', 'status' => 'pending_secretary']);
    $response = $this->actingAs(documentOfficer())->get(route('department.submission.file', [$submission, 'preview' => 1]))->assertOk();
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline;');
    $this->actingAs(documentOfficer('koordinator_departemen', 'humas'))->get(route('department.submission.file', [$submission, 'preview' => 1]))->assertNotFound();
});
