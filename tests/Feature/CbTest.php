<?php

use App\Models\CbActivity;
use App\Models\CbParticipant;
use App\Models\HimsiRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->cbDisk = Storage::fake('cb-test');
    $localDisk = Storage::disk('local');
    Storage::partialMock()->shouldReceive('build')->andReturn($this->cbDisk);
    Storage::shouldReceive('disk')->with('local')->andReturn($localDisk);
});

function cbManager(): User
{
    return User::factory()->create(['role' => 'panitia_cb', 'is_active' => true]);
}

function cbRegistration(): HimsiRegistration
{
    return HimsiRegistration::factory()->create([
        'user_id' => User::factory()->create(['role' => 'pending', 'is_active' => false])->id,
        'nim' => 'D07124001', 'angkatan' => 2024, 'program_studi' => 'Sistem Informasi',
        'phone' => '08123456789', 'status' => 'pending', 'student_card_path' => 'card.pdf',
        'certificate_path' => 'cert.pdf', 'active_letter_path' => 'active.pdf',
    ]);
}

function cbReview(array $changes = []): array
{
    return array_replace(['decision' => 'approved', 'version' => 0, 'note' => 'Berkas telah diperiksa.', 'reviews' => ['student_card' => 'valid', 'active_letter' => 'valid', 'certificate' => 'valid']], $changes);
}

function cbActivity(): CbActivity
{
    return CbActivity::factory()->create(['title' => 'CB 2026', 'period' => '2026', 'location' => 'Kampus', 'description' => 'Kegiatan kaderisasi', 'starts_at' => now(), 'ends_at' => now()->addDay(), 'status' => 'planned']);
}

it('protects CB routes and redirects its dashboard', function () {
    $this->get(route('cb.dashboard'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]))->get(route('cb.dashboard'))->assertForbidden();
    $this->actingAs(cbManager())->get(route('dashboard'))->assertRedirect(route('cb.dashboard'));
    $this->actingAs(User::factory()->create(['role' => 'panitia_cb', 'is_active' => false]))->get(route('cb.dashboard'))->assertRedirect(route('login'));
});

it('renders CB list and create screens', function (string $name) {
    $this->actingAs(cbManager())->get(route($name))->assertOk();
})->with(['cb.dashboard', 'cb.recruitment', 'cb.recruitment.create', 'cb.participants', 'cb.participants.create', 'cb.activities', 'cb.activities.create']);

it('renders record screens and permits super admin', function () {
    $registration = cbRegistration();
    $activity = cbActivity();
    $participant = CbParticipant::factory()->create(['period' => '2026', 'name' => 'Peserta', 'nim' => 'D001', 'angkatan' => 2024]);
    $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));
    $this->get(route('cb.recruitment.show', $registration))->assertOk();
    $this->get(route('cb.participants.edit', $participant))->assertOk();
    foreach (['cb.activities.show', 'cb.activities.edit', 'cb.activities.report'] as $name) {
        $this->get(route($name, $activity))->assertOk();
    }
});

it('requires actual documents for approval and activates only a reviewed applicant', function () {
    $registration = cbRegistration();
    $this->actingAs(cbManager())->post(route('cb.recruitment.review', $registration), cbReview())->assertSessionHasErrors('decision');
    foreach (['card.pdf', 'cert.pdf', 'active.pdf'] as $file) {
        $this->cbDisk->put($file, '%PDF-1.4 test');
    }
    $this->post(route('cb.recruitment.review', $registration), cbReview())->assertSessionHasNoErrors();
    expect($registration->fresh()->status)->toBe('approved');
    expect($registration->user->fresh()->role)->toBe('kbh');
    expect($registration->user->fresh()->is_active)->toBeTrue();
    $this->post(route('cb.recruitment.review', $registration), cbReview(['version' => 1, 'decision' => 'rejected']))->assertSessionHasErrors('review');
});

it('does not overwrite a privileged role or stale review', function () {
    $registration = cbRegistration();
    $registration->user->forceFill(['role' => 'super_admin'])->save();
    foreach (['card.pdf', 'cert.pdf', 'active.pdf'] as $file) {
        $this->cbDisk->put($file, '%PDF-1.4 test');
    }
    $this->actingAs(cbManager())->post(route('cb.recruitment.review', $registration), cbReview())->assertSessionHasErrors('decision');
    $this->post(route('cb.recruitment.review', $registration), cbReview(['version' => 4, 'decision' => 'returned']))->assertSessionHasErrors('review');
    expect($registration->user->fresh()->role)->toBe('super_admin');
});

it('returns and replaces documents for another review without activating the account', function () {
    $registration = cbRegistration();
    $this->actingAs(cbManager())->post(route('cb.recruitment.review', $registration), cbReview(['decision' => 'returned']))->assertSessionHasNoErrors();
    expect($registration->user->fresh()->is_active)->toBeFalse();
    $this->post(route('cb.recruitment.replace', $registration), ['version' => 1, 'student_card' => UploadedFile::fake()->create('ktm.pdf', 5, 'application/pdf')])->assertSessionHasNoErrors();
    expect($registration->fresh()->status)->toBe('pending');
    expect($registration->fresh()->review_version)->toBe(2);
    $this->cbDisk->assertExists($registration->fresh()->student_card_path);
});

it('limits enrollment and locks attendance when completed', function () {
    $activity = cbActivity();
    $participant = CbParticipant::factory()->create(['period' => '2026', 'name' => 'Peserta', 'nim' => 'D001', 'angkatan' => 2024, 'status' => 'pending']);
    $this->actingAs(cbManager())->post(route('cb.activities.enroll', $activity), ['participants' => [$participant->id]])->assertSessionHasErrors('participants');
    $participant->update(['status' => 'accepted']);
    $this->post(route('cb.activities.enroll', $activity), ['participants' => [$participant->id]])->assertSessionHasNoErrors();
    $this->post(route('cb.activities.enroll', $activity), ['participants' => [$participant->id]])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('cb_attendances', 1);
    $attendance = DB::table('cb_attendances')->first();
    $this->post(route('cb.activities.attendance', [$activity, $attendance->id]), ['status' => 'present'])->assertSessionHasNoErrors();
    $activity->update(['status' => 'completed']);
    $this->post(route('cb.activities.attendance', [$activity, $attendance->id]), ['status' => 'absent'])->assertSessionHasErrors('activity');
    $this->assertDatabaseHas('cb_attendances', ['id' => $attendance->id, 'status' => 'present']);
});

it('requires a report to complete an activity and converts WITA schedule', function () {
    $data = ['title' => 'Kegiatan', 'period' => '2026', 'location' => 'Kampus', 'description' => 'Deskripsi', 'starts_at' => '2026-10-20T09:00', 'ends_at' => '2026-10-20T17:00', 'status' => 'completed'];
    $this->actingAs(cbManager())->post(route('cb.activities.store'), $data)->assertSessionHasErrors('report');
    $this->post(route('cb.activities.store'), $data + ['report' => 'Kegiatan telah selesai.'])->assertSessionHasNoErrors();
    expect(CbActivity::first()->starts_at->timezone('Asia/Makassar')->format('H:i'))->toBe('09:00');
});

it('scopes private attachments to the activity and denies other roles', function () {
    Storage::fake('local');
    $activity = cbActivity();
    $this->actingAs(cbManager())->post(route('cb.activities.upload', $activity), ['title' => 'Laporan', 'kind' => 'report', 'file' => UploadedFile::fake()->create('laporan.pdf', 5, 'application/pdf')])->assertSessionHasNoErrors();
    $attachment = DB::table('cb_attachments')->first();
    $this->get(route('cb.activities.file', [$activity, $attachment->id]))->assertOk();
    $this->get(route('cb.activities.file', [cbActivity(), $attachment->id]))->assertNotFound();
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))->get(route('cb.activities.file', [$activity, $attachment->id]))->assertForbidden();
});

it('creates a manual applicant without accepting injected permissions', function () {
    $this->actingAs(cbManager())->post(route('cb.recruitment.store'), [
        'name' => 'Pendaftar Manual', 'email' => 'manual@example.test', 'nim' => 'D07125009',
        'angkatan' => 2025, 'phone' => '08123456789', 'password' => 'TestingManual2026!',
        'password_confirmation' => 'TestingManual2026!', 'role' => 'super_admin', 'is_active' => true,
    ])->assertSessionHasNoErrors();
    $user = User::where('email', 'manual@example.test')->firstOrFail();
    expect($user->role)->toBe('pending');
    expect($user->is_active)->toBeFalse();
    expect(HimsiRegistration::where('user_id', $user->id)->first()->status)->toBe('pending');
});

it('keeps participants in their enrolled period and refuses later rejection', function () {
    $participant = CbParticipant::factory()->create(['period' => '2026', 'name' => 'Peserta', 'nim' => 'D001', 'angkatan' => 2024, 'status' => 'accepted']);
    $activity = cbActivity();
    $this->actingAs(cbManager())->post(route('cb.activities.enroll', $activity), ['participants' => [$participant->id]])->assertSessionHasNoErrors();
    $this->post(route('cb.participants.select', $participant), ['status' => 'rejected', 'selection_note' => 'Seleksi ulang'])->assertSessionHasErrors('status');
    $this->put(route('cb.participants.update', $participant), ['period' => '2027', 'name' => 'Peserta', 'nim' => 'D001', 'angkatan' => 2024])->assertSessionHasErrors('period');
    expect($participant->fresh()->status)->toBe('accepted');
    expect($participant->fresh()->period)->toBe('2026');
});
