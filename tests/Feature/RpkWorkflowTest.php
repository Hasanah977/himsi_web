<?php

use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function rpkOfficer(string $role = 'koordinator_departemen', string $department = 'kaderisasi'): User
{
    return User::factory()->create(['role' => $role, 'department' => $department, 'is_active' => true]);
}

function rpkProgram(): OpsProgram
{
    $program = OpsProgram::factory()->create(['status' => 'approved']);
    OpsSubmission::factory()->create(['program_id' => $program, 'type' => 'letter', 'status' => 'approved']);
    OpsSubmission::factory()->create(['program_id' => $program, 'type' => 'budget', 'status' => 'disbursed']);

    return $program->refresh();
}

it('requires approved proposal and budget before PPRK upload', function (): void {
    Storage::fake('local');
    $coordinator = rpkOfficer();
    $program = OpsProgram::factory()->create(['status' => 'approved']);
    $url = route('department.submission.save', ['kaderisasi', $program, 'rpk']);
    $data = fn () => ['version' => 0, 'action' => 'submit', 'file' => UploadedFile::fake()->create('PPRK.pdf', 15, 'application/pdf')];
    $this->actingAs($coordinator)->post($url, $data())->assertSessionHasErrors('workflow');
    OpsSubmission::factory()->create(['program_id' => $program, 'type' => 'letter', 'status' => 'approved']);
    $this->post($url, $data())->assertSessionHasErrors('workflow');
    $budget = OpsSubmission::factory()->create(['program_id' => $program, 'type' => 'budget', 'status' => 'pending_chair']);
    $this->post($url, $data())->assertSessionHasErrors('workflow');
    $budget->update(['status' => 'ready_funding']);
    $this->post($url, $data())->assertSessionHasNoErrors();
    $rpk = $program->submissions()->where('type', 'rpk')->sole();
    expect($rpk->status)->toBe('pending_officers');
    Storage::disk('local')->assertExists($rpk->file_path);
});

it('verifies all three officers then supervisor and secretary archive before starting a program', function (): void {
    Storage::fake('local');
    $coordinator = rpkOfficer();
    $officers = collect(['sekretaris_umum', 'bendahara_umum', 'ketua_umum'])->mapWithKeys(fn ($role) => [$role => rpkOfficer($role)]);
    $supervisor = rpkOfficer('koordinator_pengawas');
    $program = rpkProgram();
    $this->actingAs($coordinator)->get(route('department.submission.edit', ['kaderisasi', $program, 'rpk']))->assertSee('Unggah PPRK');
    $this->post(route('department.submission.save', ['kaderisasi', $program, 'rpk']), ['version' => 0, 'action' => 'submit', 'file' => UploadedFile::fake()->create('PPRK.pdf', 15, 'application/pdf')])->assertSessionHasNoErrors();
    $rpk = $program->submissions()->where('type', 'rpk')->sole();
    expect(NotifikasiHimsi::where('judul', 'PPRK menunggu verifikasi')->pluck('user_id')->sort()->values()->all())->toBe($officers->pluck('id')->sort()->values()->all());
    $this->post(route('department.program.transition', ['kaderisasi', $program]), ['version' => $program->version, 'action' => 'start'])->assertSessionHasErrors('workflow');
    $this->actingAs($supervisor)->get(route('department.submission.file', $rpk))->assertNotFound();
    $this->get(route('department.reviews', ['type' => 'rpk']))->assertDontSee($program->title);
    $this->post(route('department.review.save', $rpk), ['version' => 1, 'action' => 'approve', 'note' => 'Terlalu awal'])->assertNotFound();
    foreach ($officers as $officer) {
        $this->actingAs($officer)->get(route('department.review', $rpk))->assertSee('Verifikasi PPRK');
        $this->post(route('department.review.save', $rpk), ['version' => $rpk->refresh()->version, 'action' => 'approve', 'note' => 'PPRK telah sesuai'])->assertSessionHasNoErrors();
    }
    expect($rpk->refresh()->status)->toBe('pending_supervisor');
    expect($rpk->payload['approvals'])->toHaveCount(3);
    $this->actingAs($supervisor)->get(route('department.reviews', ['type' => 'rpk']))->assertSee($program->title);
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $supervisor->id, 'judul' => 'PPRK menunggu tindakan']);
    $this->actingAs($supervisor)->post(route('department.review.save', $rpk), ['version' => $rpk->version, 'action' => 'approve', 'note' => 'Layak dilaksanakan'])->assertSessionHasNoErrors();
    expect($rpk->refresh()->status)->toBe('pending_archive');
    $this->actingAs($coordinator)->post(route('department.program.transition', ['kaderisasi', $program]), ['version' => $program->version, 'action' => 'start'])->assertSessionHasErrors('workflow');
    $this->actingAs($officers['sekretaris_umum'])->get(route('department.review', $rpk))->assertSee('Arsipkan PPRK')->assertDontSee('Arsipkan LPJ dan selesaikan proker');
    $this->post(route('department.review.save', $rpk), ['version' => $rpk->refresh()->version, 'action' => 'archive', 'note' => 'PPRK diarsipkan'])->assertSessionHasNoErrors();
    expect($rpk->refresh()->status)->toBe('archived');
    expect($program->fresh()->status)->toBe('approved');
    $this->get(route('sekretaris.arsip-surat.index', ['tab' => 'rpk']))->assertSee($program->title)->assertSee('Lihat PPRK');
    $this->actingAs($coordinator)->post(route('department.program.transition', ['kaderisasi', $program]), ['version' => $program->version, 'action' => 'start'])->assertSessionHasNoErrors();
    expect($program->fresh()->status)->toBe('running');
});

it('rejects duplicate officer approvals and stale versions', function (): void {
    $secretary = rpkOfficer('sekretaris_umum');
    $treasurer = rpkOfficer('bendahara_umum');
    $rpk = OpsSubmission::factory()->create(['program_id' => rpkProgram(), 'type' => 'rpk', 'status' => 'pending_officers']);
    $url = route('department.review.save', $rpk);
    $this->actingAs($secretary)->post($url, ['version' => 0, 'action' => 'approve', 'note' => 'Sesuai'])->assertSessionHasNoErrors();
    $this->post($url, ['version' => 1, 'action' => 'approve', 'note' => 'Duplikat'])->assertForbidden();
    $this->actingAs($treasurer)->post($url, ['version' => 0, 'action' => 'approve', 'note' => 'Lama'])->assertSessionHasErrors('version');
    expect($rpk->refresh()->payload['approvals'])->toHaveCount(1);
    expect($rpk->status)->toBe('pending_officers');
});

it('resets approvals and requires a new file when a revised PPRK is resubmitted', function (): void {
    Storage::fake('local');
    $program = rpkProgram();
    $rpk = OpsSubmission::factory()->create(['program_id' => $program, 'type' => 'rpk', 'status' => 'pending_officers', 'file_path' => 'old.pdf', 'payload' => ['approvals' => ['sekretaris_umum' => ['user_id' => 1, 'name' => 'Sekertaris']]]]);
    $this->actingAs(rpkOfficer('bendahara_umum'))->post(route('department.review.save', $rpk), ['version' => 0, 'action' => 'revision', 'note' => 'Perbaiki jadwal'])->assertSessionHasNoErrors();
    $url = route('department.submission.save', ['kaderisasi', $program, 'rpk']);
    $this->actingAs(rpkOfficer())->post($url, ['version' => 1, 'action' => 'submit'])->assertSessionHasErrors('workflow');
    $this->post($url, ['version' => 1, 'action' => 'submit', 'file' => UploadedFile::fake()->create('PPRK-baru.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    expect($rpk->refresh()->status)->toBe('pending_officers');
    expect($rpk->refresh()->payload)->not->toHaveKey('approvals');
    Storage::disk('local')->assertExists($rpk->file_path);
});

it('protects PPRK across departments and roles and renders its list', function (): void {
    $program = rpkProgram();
    $rpk = OpsSubmission::factory()->create(['program_id' => $program, 'type' => 'rpk', 'status' => 'pending_officers']);
    $this->actingAs(rpkOfficer())->get(route('department.submissions', ['kaderisasi', 'rpk']))->assertSee('PPRK');
    $this->get(route('department.program.show', ['kaderisasi', $program]))->assertSee('PPRK');
    $this->actingAs(rpkOfficer('koordinator_departemen', 'humas'))->get(route('department.submission.file', $rpk))->assertNotFound();
    $this->post(route('department.submission.save', ['humas', $program, 'rpk']), ['version' => 0, 'action' => 'submit'])->assertNotFound();
    $this->actingAs(rpkOfficer('anggota_departemen'))->post(route('department.review.save', $rpk), ['version' => 0, 'action' => 'approve', 'note' => 'Tidak berwenang'])->assertForbidden();
    expect($rpk->refresh()->status)->toBe('pending_officers');
});
