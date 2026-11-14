<?php

use App\Models\HimsiRegistration;
use App\Models\NotifikasiHimsi;
use App\Models\OpsGroup;
use App\Models\PublicContent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    $this->withoutVite();
});

it('lets applicants log in only to review and blocks all KBH routes', function (string $status) {
    $registration = HimsiRegistration::factory()->create(['status' => $status]);
    $this->post(route('login.submit'), ['email' => $registration->user->email, 'password' => 'password'])->assertRedirect(route('registration.review'));
    $this->get(route('registration.review'))->assertSee('Pemeriksaan Berkas');
    $this->get(route('kbh.dashboard'))->assertRedirect(route('registration.review'));
    $this->get(route('kbh.forum'))->assertRedirect(route('registration.review'));
    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();
})->with(['pending', 'returned', 'rejected']);

it('denies disabled members and logs approved members into their dashboard', function () {
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => false]);
    HimsiRegistration::factory()->create(['user_id' => $user->id, 'status' => 'approved']);
    $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
    $user->forceFill(['is_active' => true])->save();
    $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('role.welcome'));
    $this->get(route('dashboard'))->assertRedirect(route('kbh.dashboard'));
});

it('resubmits revised private documents and lets CB approve the newest version', function () {
    $disk = Storage::fake('applicant-test');
    Storage::partialMock()->shouldReceive('build')->andReturn($disk);
    foreach (['card.pdf', 'active.pdf', 'cert.pdf'] as $path) {
        $disk->put($path, '%PDF-1.4 test');
    }
    $registration = HimsiRegistration::factory()->create(['status' => 'returned', 'review_note' => 'Perjelas KTM', 'student_card_path' => 'card.pdf', 'active_letter_path' => 'active.pdf', 'certificate_path' => 'cert.pdf']);
    $this->actingAs($registration->user)->get(route('registration.document', 'student_card'))->assertHeader('content-disposition', 'inline; filename=student_card.pdf');
    $this->get(route('registration.document', ['student_card', 'download' => 1]))->assertDownload('student_card.pdf');
    $this->post(route('registration.revise'), ['version' => 0, 'student_card' => UploadedFile::fake()->create('new.pdf', 5, 'application/pdf')])->assertSessionHasNoErrors();
    $registration->refresh();
    expect($registration->status)->toBe('pending');
    expect($registration->review_version)->toBe(1);
    $disk->assertExists($registration->student_card_path);
    $disk->assertMissing('card.pdf');
    $this->post(route('registration.revise'), ['version' => 1, 'student_card' => UploadedFile::fake()->create('again.pdf', 5, 'application/pdf')])->assertForbidden();
    $manager = User::factory()->create(['role' => 'panitia_cb', 'is_active' => true]);
    $this->actingAs($manager)->post(route('cb.recruitment.review', $registration), ['version' => 0, 'decision' => 'approved', 'note' => 'Valid', 'reviews' => ['student_card' => 'valid', 'active_letter' => 'valid', 'certificate' => 'valid']])->assertSessionHasErrors('review');
    $this->post(route('cb.recruitment.review', $registration), ['version' => 1, 'decision' => 'approved', 'note' => 'Semua valid', 'reviews' => ['student_card' => 'valid', 'active_letter' => 'valid', 'certificate' => 'valid']])->assertSessionHasNoErrors();
    expect($registration->fresh()->status)->toBe('approved');
    $this->actingAs($registration->user->fresh())->get(route('kbh.dashboard'))->assertOk();
});

it('rejects revisions after rejection and never exposes another applicants files', function () {
    $disk = Storage::fake('private-test');
    Storage::partialMock()->shouldReceive('build')->andReturn($disk);
    $other = HimsiRegistration::factory()->create(['student_card_path' => 'other.pdf']);
    $disk->put('other.pdf', 'private');
    $registration = HimsiRegistration::factory()->create(['status' => 'rejected']);
    $this->actingAs($registration->user)->get(route('registration.document', 'student_card'))->assertNotFound();
    $this->get(route('cb.recruitment.document', [$other, 'student_card']))->assertRedirect(route('registration.review'));
    $this->post(route('registration.revise'), ['version' => 0, 'student_card' => UploadedFile::fake()->create('card.pdf', 5, 'application/pdf')])->assertForbidden();
    $disk->assertExists('other.pdf');
});

it('validates revision file type size and version', function () {
    $registration = HimsiRegistration::factory()->create(['status' => 'returned']);
    $this->actingAs($registration->user)->post(route('registration.revise'), ['version' => 0, 'student_card' => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf')])->assertSessionHasErrors('student_card');
    $this->post(route('registration.revise'), ['version' => 0, 'student_card' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->assertSessionHasErrors('student_card');
    $this->post(route('registration.revise'), ['version' => 5, 'student_card' => UploadedFile::fake()->create('valid.pdf', 1, 'application/pdf')])->assertSessionHasErrors('review');
    expect($registration->fresh()->status)->toBe('returned');
});

it('updates email phone and photo without changing name nim or role', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $registration = HimsiRegistration::factory()->create(['user_id' => $user->id, 'status' => 'approved']);
    $this->actingAs($user)->put(route('kbh.profil.update'), ['email' => 'new@example.test', 'no_hp' => '081111222333', 'nama_lengkap' => 'Changed', 'nim' => 'HACK', 'role' => 'super_admin', 'foto_profile' => UploadedFile::fake()->image('photo.png')])->assertSessionHasNoErrors();
    expect($user->fresh()->name)->toBe($user->name);
    expect($user->fresh()->role)->toBe('kbh');
    expect($user->fresh()->email)->toBe('new@example.test');
    expect($registration->fresh()->nim)->toBe($registration->nim);
    expect($registration->fresh()->phone)->toBe('081111222333');
    Storage::disk('local')->assertExists($user->fresh()->foto_profile);
    $this->get(route('kbh.profil.photo'))->assertOk();
    $this->put(route('kbh.profil.password.update'), ['password_lama' => 'password', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasNoErrors();
    expect(Hash::check('NewPassword123', $user->fresh()->password))->toBeTrue();
});

it('rejects oversized photos duplicate emails and incorrect current passwords', function () {
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $other = User::factory()->create();
    $this->actingAs($user)->put(route('kbh.profil.update'), ['email' => $other->email, 'no_hp' => '081111222333', 'foto_profile' => UploadedFile::fake()->image('photo.png')->size(5121)])->assertSessionHasErrors(['email', 'foto_profile']);
    $this->put(route('kbh.profil.password.update'), ['password_lama' => 'wrong', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasErrors('password_lama');
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('shows private unread notifications and authorizes deletion', function () {
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $other = User::factory()->create();
    NotifikasiHimsi::kirim($user->id, 'Pesan untuk saya', 'Isi', route('kbh.iuran.index', [], false), 'iuran');
    NotifikasiHimsi::kirim($other->id, 'Rahasia', 'Privat');
    $mine = NotifikasiHimsi::where('user_id', $user->id)->first();
    $theirs = NotifikasiHimsi::where('user_id', $other->id)->first();
    $this->actingAs($user)->get(route('kbh.notifikasi.index'))->assertSee('Belum dibaca')->assertSee('Notifikasi, 1 belum dibaca')->assertDontSee('Rahasia');
    $this->delete(route('notifikasi-himsi.destroy', $theirs))->assertForbidden();
    $this->post(route('notifikasi-himsi.baca', $mine))->assertRedirect(route('kbh.iuran.index', [], false));
    $this->get(route('kbh.notifikasi.index'))->assertSee('Sudah dibaca')->assertSee('Notifikasi, 0 belum dibaca');
    $this->delete(route('notifikasi-himsi.destroy', $mine))->assertSessionHasNoErrors();
    $this->assertModelMissing($mine);
    $this->assertModelExists($theirs);
});

it('routes aspirations to Humas and refuses other coordinators', function () {
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $humas = User::factory()->create(['role' => 'koordinator_departemen', 'department' => 'humas', 'is_active' => true]);
    $other = User::factory()->create(['role' => 'koordinator_departemen', 'department' => 'advokasi', 'is_active' => true]);
    $this->actingAs($user)->post(route('kbh.aspirasi.store'), ['judul' => 'Usulan KBH', 'isi' => 'Adakan diskusi'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $humas->id, 'judul' => 'Aspirasi KBH baru']);
    $this->actingAs($humas)->get(route('administration.aspirations'))->assertSee('Usulan KBH');
    $this->actingAs($other)->get(route('administration.aspirations'))->assertForbidden();
});

it('automatically includes KBH in forums but only super admin can create groups', function () {
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($member)->get(route('kbh.forum'))->assertSee('Keluarga Besar HIMSI')->assertDontSee('Buat Grup KBH');
    $this->post(route('kbh.forum.store'), ['name' => 'Forbidden'])->assertForbidden();
    $this->actingAs($admin)->post(route('kbh.forum.store'), ['name' => 'Diskusi KBH'])->assertSessionHasNoErrors();
    $group = OpsGroup::where('name', 'Diskusi KBH')->firstOrFail();
    $this->actingAs($member)->post(route('kbh.forum.send', $group), ['body' => '<script>alert(1)</script>'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('ops_messages', ['group_id' => $group->id, 'user_id' => $member->id]);
    $this->get(route('kbh.forum', ['group' => $group->id]))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $private = OpsGroup::factory()->create(['department' => 'humas']);
    $this->get(route('kbh.forum', ['group' => $private->id]))->assertNotFound();
    $this->post(route('kbh.forum.send', $private), ['body' => 'Intrusion'])->assertNotFound();
    $this->actingAs(User::factory()->create(['role' => 'panitia_cb', 'is_active' => true]))->get(route('kbh.forum'))->assertForbidden();
});

it('shows payment details and records visits for activity indicators', function () {
    $this->freezeTime();
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    config(['himsi_iuran.nama_bank' => 'BRI', 'himsi_iuran.nomor_rekening' => '0012-01-054321-50-3', 'himsi_iuran.nama_pemilik' => 'HIMPUNAN MAHASISWA SISTEM INFORMASI', 'himsi_iuran.qr_url' => '/images/himsi/QR%20CODE.png']);
    PublicContent::create(['section' => 'announcements', 'slug' => 'baru', 'is_published' => true, 'position' => 0, 'payload' => ['title' => 'Pengumuman baru', 'summary' => 'Isi']]);
    $this->actingAs($user)->get(route('kbh.dashboard'))->assertSee('Pengumuman baru')->assertDontSee('Akses Cepat')->assertSee(route('announcements'), false);
    $this->get(route('kbh.pengumuman.index'))->assertOk();
    $this->get(route('kbh.iuran.index'))->assertSee('0012-01-054321-50-3')->assertSee('/images/himsi/QR%20CODE.png', false);
    $this->assertDatabaseHas('kbh_activity_reads', ['user_id' => $user->id, 'route' => 'kbh.iuran.index']);
});
