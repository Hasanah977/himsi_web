<?php

use App\Models\HimsiRegistration;
use App\Models\KbhAspirasi;
use App\Models\OpsGroup;
use App\Models\OpsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    $this->withoutVite();
});

it('uploads a profile photo independently of contact information', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($user)->post(route('kbh.profil.photo.update'), ['foto_profile' => UploadedFile::fake()->image('avatar.png')])->assertSessionHasNoErrors();
    Storage::disk('local')->assertExists($user->fresh()->foto_profile);
    expect($user->fresh()->email)->toBe($user->email);
    $this->post(route('kbh.profil.photo.update'), ['foto_profile' => UploadedFile::fake()->image('large.png')->size(5121)])->assertSessionHasErrors('foto_profile');
});

it('supports private attachments and owner only message edits and deletion', function (string $role, string $prefix, string $department) {
    Storage::fake('local');
    $user = User::factory()->create(['role' => $role, 'department' => $department, 'is_active' => true]);
    $other = User::factory()->create(['role' => $role, 'department' => $department, 'is_active' => true]);
    $group = OpsGroup::factory()->create(['department' => $department, 'system_key' => 'test-'.$department]);
    $args = $prefix === 'kbh' ? [$group->id] : [$department, $group->id];
    $this->actingAs($user)->post(route($prefix.'.forum.send', $args), ['file' => UploadedFile::fake()->create('agenda.pdf', 2, 'application/pdf')])->assertSessionHasNoErrors();
    $message = OpsMessage::where('group_id', $group->id)->firstOrFail();
    $path = $message->file_path;
    Storage::disk('local')->assertExists($path);
    $args = $prefix === 'kbh' ? [$message->id] : [$department, $message->id];
    $this->get(route($prefix.'.forum.file', $args))->assertDownload('agenda.pdf');
    $this->actingAs($other)->put(route($prefix.'.forum.update', $args), ['body' => 'Hijacked'])->assertForbidden();
    $this->delete(route($prefix.'.forum.destroy', $args))->assertForbidden();
    $this->actingAs($user)->put(route($prefix.'.forum.update', $args), ['body' => 'Agenda terbaru'])->assertSessionHasNoErrors();
    expect($message->fresh()->body)->toBe('Agenda terbaru');
    $this->delete(route($prefix.'.forum.destroy', $args))->assertSessionHasNoErrors();
    $this->assertModelMissing($message);
    Storage::disk('local')->assertMissing($path);
})->with([['kbh', 'kbh', 'kbh'], ['koordinator_departemen', 'department', 'humas'], ['anggota_departemen', 'department', 'humas']]);

it('rejects invalid forum attachments and access outside the group', function () {
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $group = OpsGroup::where('system_key', 'kbh')->firstOrFail();
    $this->actingAs($user)->post(route('kbh.forum.send', $group), ['file' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('file');
    $this->post(route('kbh.forum.send', $group), [])->assertSessionHasErrors(['body', 'file']);
    $private = OpsMessage::factory()->create(['group_id' => OpsGroup::factory()->create(['department' => 'humas'])->id]);
    $this->get(route('kbh.forum.file', $private))->assertNotFound();
    $this->put(route('kbh.forum.update', $private), ['body' => 'Changed'])->assertNotFound();
    $this->delete(route('kbh.forum.destroy', $private))->assertNotFound();
    $this->assertModelExists($private);
});

it('renders the revised member pages with actual conversation and aspiration data', function () {
    $user = User::factory()->create(['name' => 'Nadia Putri', 'role' => 'kbh', 'is_active' => true]);
    HimsiRegistration::factory()->create(['user_id' => $user->id, 'status' => 'approved', 'nim' => 'D0424316']);
    $other = User::factory()->create(['name' => 'Rizki Pratama', 'role' => 'kbh', 'is_active' => true]);
    $group = OpsGroup::where('system_key', 'kbh')->firstOrFail();
    OpsMessage::factory()->create(['group_id' => $group->id, 'user_id' => $other->id, 'body' => 'Halo teman-teman, ada yang tertarik ikut kelas persiapan magang minggu depan?']);
    OpsMessage::factory()->create(['group_id' => $group->id, 'user_id' => $user->id, 'body' => 'Saya ikut! Boleh dibagikan jadwal dan materi yang perlu dipersiapkan?']);
    KbhAspirasi::create(['user_id' => $user->id, 'judul' => 'Usulan kelas persiapan magang', 'isi' => 'Saya mengusulkan sesi berbagi pengalaman magang bersama alumni.', 'status' => 'diproses', 'response' => 'Terima kasih atas masukannya. Kami sedang berkoordinasi dengan alumni untuk menyusun jadwal.']);
    $this->actingAs($user);
    foreach (['kbh.aspirasi.index' => 'aspirasi', 'kbh.profil' => 'profil', 'kbh.forum' => 'forum', 'kbh.dashboard' => 'dashboard'] as $route => $name) {
        $response = $this->get(route($route))->assertOk();
    }
    $this->get(route('kbh.profil'))->assertSee('Ganti foto profil');
    $this->get(route('kbh.forum'))->assertSee('Opsi pesan')->assertSee('Lampiran');
    $this->get(route('kbh.aspirasi.index'))->assertSee('Sedang ditinjau')->assertSee('Tanggapan Humas');
});
