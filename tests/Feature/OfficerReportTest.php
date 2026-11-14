<?php

use App\Models\OfficerReport;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('lets each officer upload a private LPJ for supervisor verification', function (string $role) {
    Storage::fake('local');
    $officer = User::factory()->create(['role' => $role, 'is_active' => true]);
    $supervisor = User::factory()->create(['role' => 'koordinator_pengawas', 'is_active' => true]);

    $this->actingAs($officer)->post(route('officer-reports.store'), [
        'title' => 'LPJ Kegiatan', 'period' => '2026/2027', 'file' => UploadedFile::fake()->create('lpj.pdf', 10, 'application/pdf'),
        'status' => 'approved', 'user_id' => $supervisor->id,
    ])->assertSessionHasNoErrors();

    $report = OfficerReport::firstOrFail();
    expect($report)->user_id->toBe($officer->id)->status->toBe('pending_supervisor')->role->toBe($role);
    Storage::disk('local')->assertExists($report->file_path);
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $supervisor->id, 'judul' => 'LPJ Menunggu Verifikasi']);
    $this->get(route('officer-reports.index'))->assertOk()->assertSee('LPJ Kegiatan')->assertSee('Upload LPJ');
    $this->get(route('officer-reports.file', $report))->assertDownload('lpj.pdf');
    $this->actingAs($supervisor)->get(route('officer-reports.show', $report))->assertOk()->assertSee('Verifikasi LPJ');
})->with(['ketua_umum', 'sekretaris_umum', 'bendahara_umum']);

it('returns revisions to the owner and requires a fresh supervisor decision', function () {
    Storage::fake('local');
    $this->freezeTime();
    $report = OfficerReport::factory()->create();
    $supervisor = User::factory()->create(['role' => 'koordinator_pengawas', 'is_active' => true]);
    $this->actingAs($supervisor)->post(route('officer-reports.review', $report), ['version' => 1, 'status' => 'revision', 'note' => 'Lengkapi lampiran'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('officer_reports', ['id' => $report->id, 'status' => 'revision', 'reviewed_by' => $supervisor->id]);
    $this->actingAs($report->user)->get(route('officer-reports.show', $report))->assertOk()->assertSee('Lengkapi lampiran')->assertSee('Upload Revisi LPJ');
    $this->put(route('officer-reports.update', $report), ['version' => 2, 'file' => UploadedFile::fake()->create('revisi.pdf', 20, 'application/pdf')])->assertSessionHasNoErrors();
    $report->refresh();
    expect($report)->status->toBe('pending_supervisor')->reviewed_by->toBeNull()->reviewed_at->toBeNull()->version->toBe(3);
    Storage::disk('local')->assertExists($report->file_path);
    $this->actingAs($supervisor)->post(route('officer-reports.review', $report), ['version' => 1, 'status' => 'approved', 'note' => 'Usang'])->assertSessionHasErrors('version');
    $this->post(route('officer-reports.review', $report), ['version' => 3, 'status' => 'approved', 'note' => 'Lengkap'])->assertSessionHasNoErrors();
    expect($report->fresh())->status->toBe('approved')->history->toHaveCount(3);
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $report->user_id, 'judul' => 'Pembaruan LPJ']);
    $this->post(route('officer-reports.review', $report), ['version' => 4, 'status' => 'revision', 'note' => 'Ulang'])->assertSessionHasErrors('workflow');
    $this->actingAs($report->user)->put(route('officer-reports.update', $report), ['version' => 4, 'file' => UploadedFile::fake()->create('ganti.pdf', 10, 'application/pdf')])->assertSessionHasErrors('workflow');
});

it('protects LPJ files and decisions from other officers and unrelated roles', function (string $role) {
    $report = OfficerReport::factory()->create(['title' => 'Dokumen rahasia']);
    $user = User::factory()->create(['role' => $role, 'is_active' => true, 'department' => 'humas']);
    $this->actingAs($user)->get(route('officer-reports.show', $report))->assertNotFound();
    $this->get(route('officer-reports.file', $report))->assertNotFound();
    $this->post(route('officer-reports.review', $report), ['version' => 1, 'status' => 'approved', 'note' => 'Tidak sah'])->assertForbidden();
    $this->put(route('officer-reports.update', $report), [])->assertForbidden();
    if (in_array($role, OfficerReport::SubmitterRoles, true)) {
        $this->get(route('officer-reports.index'))->assertOk()->assertDontSee('Dokumen rahasia');
    } else {
        $this->get(route('officer-reports.index'))->assertForbidden();
        $this->post(route('officer-reports.store'), [])->assertForbidden();
    }
    expect($report->fresh()->status)->toBe('pending_supervisor');
})->with(['ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen', 'kbh']);

it('validates uploads and filters without saving invalid LPJs', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $this->actingAs($user)->post(route('officer-reports.store'), [])->assertSessionHasErrors(['title', 'period', 'file']);
    foreach ([UploadedFile::fake()->create('script.exe', 1, 'application/octet-stream'), UploadedFile::fake()->create('large.pdf', 25601, 'application/pdf')] as $file) {
        $this->post(route('officer-reports.store'), ['title' => 'LPJ', 'period' => '2026/2027', 'file' => $file])->assertSessionHasErrors('file');
    }
    $this->get(route('officer-reports.index', ['status' => 'invalid']))->assertSessionHasErrors('status');
    $this->assertDatabaseCount('officer_reports', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('filters reports and escapes their user supplied content', function () {
    $user = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $report = OfficerReport::factory()->for($user)->create(['title' => '<script>LPJ</script>', 'status' => 'approved']);
    OfficerReport::factory()->for($user)->create(['title' => 'Belum diperiksa']);
    $this->actingAs($user)->get(route('officer-reports.index', ['status' => 'approved']))->assertSee('&lt;script&gt;LPJ&lt;/script&gt;', false)->assertDontSee('<script>LPJ</script>', false)->assertDontSee('Belum diperiksa');
    $this->get(route('officer-reports.show', $report))->assertSee('&lt;script&gt;LPJ&lt;/script&gt;', false);
});

it('requires login and keeps super admin previews read only', function () {
    $this->get(route('officer-reports.index'))->assertRedirect(route('login'));
    $report = OfficerReport::factory()->create();
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($admin)->get(route('officer-reports.show', $report))->assertOk();
    $this->post(route('officer-reports.store'), [])->assertForbidden();
    $this->post(route('officer-reports.review', $report), [])->assertForbidden();
});

it('allows only the supervisor to reject and the owner to resubmit the LPJ', function () {
    Storage::fake('local');
    $report = OfficerReport::factory()->create();
    $supervisor = User::factory()->create(['role' => 'koordinator_pengawas', 'is_active' => true]);
    $this->actingAs($report->user)->post(route('officer-reports.review', $report), ['version' => 1, 'status' => 'approved', 'note' => 'Sendiri'])->assertForbidden();
    $this->actingAs($supervisor)->post(route('officer-reports.review', $report), ['version' => 1, 'status' => 'archived', 'note' => 'Tidak valid'])->assertSessionHasErrors('status');
    $this->post(route('officer-reports.review', $report), ['version' => 1, 'status' => 'rejected'])->assertSessionHasErrors('note');
    $this->post(route('officer-reports.review', $report), ['version' => 1, 'status' => 'rejected', 'note' => 'Dokumen salah'])->assertSessionHasNoErrors();
    $this->actingAs($report->user)->put(route('officer-reports.update', $report), ['version' => 2, 'file' => UploadedFile::fake()->create('baru.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])->assertSessionHasNoErrors();
    $report->refresh();
    expect($report)->status->toBe('pending_supervisor')->version->toBe(3);
    Storage::disk('local')->assertExists($report->file_path);
});

it('serves the LPJ template only to authorized roles and returns not found when missing', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]);
    $this->actingAs($user)->get(route('officer-reports.template'))->assertNotFound();
    Storage::disk('local')->put(config('department.lpj_template'), 'test template');
    $this->get(route('officer-reports.template'))->assertDownload('Template-LPJ-HIMSI.docx');
    Storage::disk('local')->assertExists(config('department.lpj_template'));
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))->get(route('officer-reports.template'))->assertForbidden();
});
