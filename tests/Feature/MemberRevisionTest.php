<?php

use App\Models\HimsiRegistration;
use App\Models\KbhPendaftaranSeleksi;
use App\Models\MemberDocument;
use App\Models\NotifikasiHimsi;
use App\Models\PublicContent;
use App\Models\User;
use App\Support\MemberProfile;
use App\Support\RecruitmentControl;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);
beforeEach(function (): void {
    $this->withoutVite();
});

function revisionRegistrationData(array $changes = []): array
{
    return array_replace([
        'name' => 'Anggota Pengujian', 'nim' => 'D0424999', 'angkatan' => 2024,
        'email' => 'anggota@example.test', 'phone' => '08123456789',
        'password' => 'Rahasia2026', 'password_confirmation' => 'Rahasia2026',
        'birth_place' => 'Majene', 'birth_date' => '2005-03-14', 'semester' => 5,
        'gender' => 'Perempuan', 'religion' => 'Islam', 'guardian_name' => 'Wali Pengujian',
        'guardian_phone' => '08123456780', 'nik' => '7601010101010101', 'nisn' => '1234567890',
        'address_street' => 'Jalan Kampus', 'address_rt' => '01', 'address_rw' => '02',
        'address_hamlet' => 'Lingkungan Kampus', 'address_village' => 'Lembang',
        'address_district' => 'Banggae Timur', 'address_city' => 'Majene',
        'address_province' => 'Sulawesi Barat', 'address_postcode' => '91412',
        'certificate' => UploadedFile::fake()->create('sertifikat.pdf', 3, 'application/pdf'),
        'active_letter' => UploadedFile::fake()->create('aktif.pdf', 3, 'application/pdf'),
        'student_card' => UploadedFile::fake()->create('ktm.pdf', 3, 'application/pdf'),
    ], $changes);
}

it('saves complete registration identity and family details with private documents', function (): void {
    $disk = Storage::fake('registration-revision');
    Storage::partialMock()->shouldReceive('build')->andReturn($disk);
    $data = revisionRegistrationData(['role' => 'super_admin', 'is_active' => true]);
    $this->post(route('registration.store'), $data)->assertRedirect(route('registration.success'))->assertSessionHasNoErrors();
    $registration = HimsiRegistration::where('nim', $data['nim'])->firstOrFail();
    expect($registration->semester)->toBe(5)
        ->and($registration->birth_date->format('Y-m-d'))->toBe('2005-03-14')
        ->and($registration->guardian_name)->toBe('Wali Pengujian')
        ->and($registration->address_village)->toBe('Lembang')
        ->and($registration->user->role)->toBe('pending')
        ->and(Hash::check($data['password'], $registration->user->password))->toBeTrue();
    $disk->assertExists($registration->student_card_path);
    $this->assertGuest();
});

it('validates new identity details without flashing passwords', function (string $field, mixed $value): void {
    $this->from(route('registration.create'))->post(route('registration.store'), revisionRegistrationData([$field => $value]))
        ->assertRedirect(route('registration.create'))->assertSessionHasErrors($field)
        ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
    $this->assertDatabaseMissing('users', ['email' => 'anggota@example.test']);
})->with([
    'guardian is required' => ['guardian_name', ''], 'guardian phone' => ['guardian_phone', 'abc'],
    'future birthday' => ['birth_date', '2099-01-01'], 'semester bound' => ['semester', 21],
    'gender choice' => ['gender', 'other'], 'religion choice' => ['religion', 'unknown'],
    'address required' => ['address_village', ''], 'invalid identity number' => ['nik', '123'],
]);

it('allows only super admin to change recruitment access and preserves public settings', function (): void {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $settings = PublicContent::create(['section' => 'settings', 'slug' => 'site', 'payload' => ['hero_badge' => 'Tetap tampil'], 'position' => 0, 'is_published' => true]);
    $this->actingAs($admin)->put(route('admin.governance-control.update'), ['enabled' => false])->assertSessionHasNoErrors();
    expect(RecruitmentControl::enabled())->toBeFalse();
    expect($settings->fresh()->payload['hero_badge'])->toBe('Tetap tampil');
    $this->put(route('admin.governance-control.update'), ['enabled' => 'invalid'])->assertSessionHasErrors('enabled');
    $this->put(route('admin.governance-control.update'), ['enabled' => true])->assertSessionHasNoErrors();
    expect(RecruitmentControl::enabled())->toBeTrue();
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))
        ->put(route('admin.governance-control.update'), ['enabled' => false])->assertForbidden();
});

it('hides and blocks recruitment for officers until enabled again', function (string $role, string $dashboard): void {
    $control = PublicContent::create(['section' => 'settings', 'slug' => 'governance', 'payload' => ['recruitment_enabled' => false], 'position' => 0, 'is_published' => false]);
    $user = User::factory()->create(['role' => $role, 'is_active' => true]);
    $this->actingAs($user)->get(route($dashboard))->assertOk()->assertDontSee('Open Rekrutmen');
    $this->get(route('recruitment.index'))->assertForbidden();
    $control->update(['payload' => ['recruitment_enabled' => true]]);
    $this->get(route($dashboard))->assertOk()->assertSee('Open Rekrutmen');
    $this->get(route('recruitment.index'))->assertOk();
})->with([
    ['ketua_umum', 'ketua-umum.dashboard'], ['sekretaris_umum', 'sekretaris.dashboard'], ['bendahara_umum', 'finance.dashboard'],
]);

it('gates member selection and voting including vote submission', function (): void {
    $control = PublicContent::create(['section' => 'settings', 'slug' => 'governance', 'payload' => ['recruitment_enabled' => false], 'position' => 0, 'is_published' => false]);
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($member)->get(route('kbh.dashboard'))->assertOk()->assertDontSee('Seleksi Pengurus')->assertDontSee('>Voting', false);
    $this->get(route('kbh.voting.hasil'))->assertForbidden();
    $this->get(route('kbh.seleksi.index'))->assertForbidden();
    $this->post(route('kpum.vote.submit'), ['candidate_id' => 1])->assertForbidden();
    $control->update(['payload' => ['recruitment_enabled' => true]]);
    $this->get(route('kbh.dashboard'))->assertOk()->assertSee('Seleksi Pengurus')->assertSee('Voting');
    $this->get(route('kbh.voting.hasil'))->assertOk();
});

it('stores categorized documents and refuses another member access', function (): void {
    Storage::fake('local');
    $owner = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $other = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($owner)->post(route('member.archive.store'), ['category' => 'KRS', 'title' => 'KRS semester lima', 'document' => UploadedFile::fake()->create('krs.pdf', 3, 'application/pdf')])->assertSessionHasNoErrors();
    $document = MemberDocument::where('user_id', $owner->id)->firstOrFail();
    Storage::disk('local')->assertExists($document->path);
    $url = route('member.archive.file', ['upload', $document->id, 'file']);
    $this->get(route('member.archive', ['category' => 'KRS']))->assertSee('KRS semester lima');
    $this->get(route('member.archive', ['category' => 'KTP']))->assertDontSee('KRS semester lima');
    $this->get($url)->assertDownload('krs.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($other)->get($url)->assertNotFound();
    $this->delete(route('member.archive.destroy', $document))->assertNotFound();
    $this->assertModelExists($document);
    $this->actingAs($owner)->delete(route('member.archive.destroy', $document))->assertSessionHasNoErrors();
    $this->assertModelMissing($document);
    Storage::disk('local')->assertMissing($document->path);
});

it('rejects unsafe or oversized personal archives and nonmember access', function (): void {
    Storage::fake('local');
    $this->get(route('member.archive'))->assertRedirect(route('login'));
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($member)->post(route('member.archive.store'), ['category' => 'KTP', 'title' => 'Dokumen', 'document' => UploadedFile::fake()->create('malware.exe', 1, 'application/octet-stream')])->assertSessionHasErrors('document');
    $this->post(route('member.archive.store'), ['category' => 'KRS', 'title' => 'Dokumen', 'document' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('document');
    $this->post(route('member.archive.store'), ['category' => 'Unknown', 'title' => 'Dokumen', 'document' => UploadedFile::fake()->create('valid.pdf', 1, 'application/pdf')])->assertSessionHasErrors('category');
    $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))->get(route('member.archive'))->assertForbidden();
    $this->assertDatabaseCount('member_documents', 0);
});

it('maps registration and selection documents to the owner archive', function (): void {
    $registrationDisk = Storage::fake('archive-registration');
    $localDisk = Storage::fake('local');
    Storage::partialMock()->shouldReceive('build')->andReturn($registrationDisk);
    Storage::shouldReceive('disk')->with('local')->andReturn($localDisk);
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $registration = HimsiRegistration::factory()->create(['user_id' => $member->id, 'status' => 'approved', 'student_card_path' => 'ktm.pdf']);
    $registrationDisk->put('ktm.pdf', '%PDF-1.4 card');
    $application = KbhPendaftaranSeleksi::factory()->create(['user_id' => $member->id, 'documents' => ['khs' => 'selection/khs.pdf']]);
    Storage::disk('local')->put('selection/khs.pdf', '%PDF-1.4 grades');
    $this->actingAs($member)->get(route('member.archive'))->assertOk()->assertSee('Pendaftaran anggota')->assertSee('KHS');
    $this->get(route('member.archive.file', ['registration', $registration->id, 'student_card']))->assertDownload('student_card.pdf');
    $this->get(route('member.archive.file', ['selection', $application->id, 'khs']))->assertDownload('khs.pdf');
    $this->get(route('member.archive.file', ['registration', $registration->id, 'guardian_name']))->assertNotFound();
});

it('deletes all notifications only for the signed in account after confirmation', function (string $role): void {
    $owner = User::factory()->create(['role' => $role, 'is_active' => true]);
    $other = User::factory()->create();
    NotifikasiHimsi::kirim($owner->id, 'Milik saya', 'Pengingat pribadi');
    NotifikasiHimsi::kirim($other->id, 'Milik orang lain', 'Tetap ada');
    $this->actingAs($owner)->delete(route('notifikasi-himsi.destroy-all'))->assertSessionHasErrors('confirmed');
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $owner->id]);
    $this->delete(route('notifikasi-himsi.destroy-all'), ['confirmed' => 1])->assertRedirect();
    $this->assertDatabaseMissing('notifikasi_himsis', ['user_id' => $owner->id]);
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $other->id]);
    $this->get(route('administration.notifications'))->assertOk()->assertSee('Belum Ada Notifikasi');
})->with(['kbh', 'super_admin', 'sekretaris_umum', 'panitia_cb', 'panitia_kpum']);

it('retains appointment history after governance expires', function (): void {
    $this->travelTo(now()->setDate(2026, 11, 9)->startOfDay());
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $member = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $period = DB::table('governance_periods')->insertGetId(['name' => '2026/2027', 'starts_at' => now()->subMonth(), 'ends_at' => now()->addYear(), 'created_at' => now(), 'updated_at' => now()]);
    MemberProfile::recordAppointment($member, 'sekretaris_umum', null, 'Batch 1', 'selection:test');
    $this->actingAs($member)->withSession(['workspace.'.$member->id => 'kbh'])->get(route('kbh.profil'))->assertOk()->assertSee('Periode 2026/2027')->assertDontSee('Pernah menjabat sebagai');
    $this->actingAs($admin)->post(route('admin.governance.end', $period), ['confirmed' => 1])->assertSessionHasNoErrors();
    $member->refresh();
    expect($member->assignedRole())->toBe('kbh');
    $this->actingAs($member)->get(route('kbh.profil'))->assertSee('Pernah menjabat sebagai')->assertSee('pada periode 2026/2027');
    expect(DB::table('member_appointments')->where('user_id', $member->id)->value('ended_at'))->not->toBeNull();
});

it('keeps super admin in its own workspace with a single forum entry', function (): void {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($admin)->get(route('kbh.dashboard'))->assertRedirect(route('admin.dashboard'));
    $response = $this->get(route('kbh.forum'))->assertOk()->assertSee('Kontrol Kepengurusan')->assertDontSee('Mode KBH');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//aside//a[@href="'.route('kbh.forum').'"]')->length)->toBe(1);
    expect($xpath->query('//aside//nav//a')[0]->textContent)->toContain('Dashboard');
    expect($xpath->query('//aside//summary[contains(., "ROLE")]')->length)->toBe(0);
});

it('returns a new chair to the dashboard when recruitment is disabled', function (): void {
    PublicContent::create(['section' => 'settings', 'slug' => 'governance', 'payload' => ['recruitment_enabled' => false], 'position' => 0, 'is_published' => false]);
    $chair = User::factory()->create(['role' => 'ketua_umum', 'is_active' => true]);
    $this->actingAs($chair)->post(route('role.welcome.acknowledge'))->assertRedirect(route('dashboard'));
    expect($chair->fresh()->welcome_signature)->toBe('ketua_umum:');
});

it('escapes archive names and ignores an attempted change of document owner', function (): void {
    Storage::fake('local');
    $owner = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $other = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $title = '<script>alert(1)</script>';
    $this->actingAs($owner)->post(route('member.archive.store'), [
        'user_id' => $other->id, 'category' => 'Lainnya', 'title' => $title,
        'document' => UploadedFile::fake()->create('dokumen.pdf', 1, 'application/pdf'),
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('member_documents', ['user_id' => $owner->id, 'title' => $title]);
    $this->assertDatabaseMissing('member_documents', ['user_id' => $other->id]);
    $this->get(route('member.archive'))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee($title, false);
});
