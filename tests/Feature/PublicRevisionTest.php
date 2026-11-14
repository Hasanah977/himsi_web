<?php

use App\Models\HimsiRegistration;
use App\Models\PublicContent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);
beforeEach(function (): void {
    $this->withoutVite();
});

it('derives homepage statistics from current records instead of manual settings', function (): void {
    PublicContent::create(['section' => 'settings', 'slug' => 'site', 'payload' => ['members_count' => 999, 'programs_count' => 999, 'achievements_count' => 999]]);
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    User::factory()->create(['role' => 'anggota_departemen', 'is_active' => true]);
    User::factory()->create(['role' => 'kbh', 'is_active' => false]);
    User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    User::factory()->create(['role' => 'pending', 'is_active' => true]);
    $unapproved = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    HimsiRegistration::factory()->create(['user_id' => $unapproved->id, 'status' => 'pending']);
    $program = null;
    foreach ([['programs', true, null, 'Sedang Berjalan'], ['programs', true, null, 'Selesai'], ['programs', false, null, 'Sedang Berjalan'], ['programs', true, 'humas', 'Sedang Berjalan'], ['achievements', true, null, ''], ['achievements', false, null, ''], ['achievements', true, 'humas', '']] as $index => [$section, $published, $department, $status]) {
        $content = PublicContent::create(['section' => $section, 'slug' => 'stat-'.$index, 'is_published' => $published, 'department' => $department, 'payload' => ['title' => 'Kegiatan', 'summary' => 'Ringkasan', 'category' => 'HIMSI', 'status' => $status, 'date' => '1 Januari 2026', 'image' => 'placeholder.svg']]);
        if ($index === 0) {
            $program = $content;
        }
    }
    $this->get(route('home'))->assertOk()->assertViewHas('statistics', ['members_count' => 2, 'programs_count' => 1, 'achievements_count' => 1]);
    $member->is_active = false;
    $member->save();
    $program->update(['payload' => array_replace($program->payload, ['status' => 'Selesai'])]);
    PublicContent::where('section', 'achievements')->update(['is_published' => false]);
    $this->get(route('home'))->assertOk()->assertViewHas('statistics', ['members_count' => 1, 'programs_count' => 0, 'achievements_count' => 0]);
    foreach (['members_count', 'programs_count', 'achievements_count'] as $key) {
        expect(config('superadmin.sections.settings.fields'))->not->toHaveKey($key);
    }
});

it('uses recent public gallery photos in the hero while excluding drafts and departmental data', function (): void {
    foreach ([['public', true, null], ['draft', false, null], ['private', true, 'humas']] as [$name, $published, $department]) {
        PublicContent::create(['section' => 'gallery', 'position' => 0, 'is_published' => $published, 'department' => $department, 'payload' => ['title' => $name, 'description' => $name, 'image' => 'storage/himsi/'.$name.'.webp']]);
    }
    $this->get(route('home'))->assertOk()->assertSee('data-hero-carousel', false)->assertSee('/storage/himsi/public.webp', false)
        ->assertDontSee('/storage/himsi/draft.webp', false)->assertDontSee('/storage/himsi/private.webp', false)->assertSee('Total Program Kerja');
});

it('lets super admin upload dedicated hero photos and achievement cards', function (): void {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($admin)->post(route('admin.content.store', 'hero_slides'), [
        'data' => ['title' => 'Temu keluarga'], 'position' => 1, 'is_published' => 1,
        'uploads' => ['image' => UploadedFile::fake()->image('hero.jpg', 1200, 600)],
    ])->assertSessionHasNoErrors();
    $hero = PublicContent::where('section', 'hero_slides')->firstOrFail();
    $this->get(route('home'))->assertSee(PublicContent::imageUrl($hero->payload['image']), false);
    $this->post(route('admin.content.store', 'achievements'), [
        'slug' => 'juara-inovasi', 'position' => 1, 'is_published' => 1,
        'data' => ['title' => 'Juara Inovasi Digital', 'category' => 'Kompetisi', 'date' => '2026-11-01', 'summary' => 'Prestasi mahasiswa HIMSI.', 'body' => 'Cerita lengkap prestasi.'],
        'uploads' => ['image' => UploadedFile::fake()->image('prestasi.jpg')],
    ])->assertSessionHasNoErrors();
    $this->get(route('profile'))->assertOk()->assertSee('Juara Inovasi Digital')->assertSee('pembinaan kader');
    $this->get(route('achievement.detail', 'juara-inovasi'))->assertOk()->assertSee('Cerita lengkap prestasi.');
});

it('paginates and filters public programs while retaining the complete status summary', function (): void {
    foreach (range(1, 8) as $number) {
        PublicContent::create(['section' => 'programs', 'slug' => 'program-'.$number, 'position' => $number, 'is_published' => true,
            'payload' => ['title' => 'Program '.$number, 'summary' => 'Deskripsi kegiatan', 'category' => 'Akademik', 'date' => '01 November 2026', 'status' => $number === 8 ? 'Selesai' : 'Akan Datang', 'location' => 'Kampus', 'image' => 'placeholder.svg']]);
    }
    $this->get(route('programs'))->assertOk()->assertSee('Distribusi Status Program Kerja')->assertSee('Program 6')->assertDontSee('Program 7');
    $this->get(route('programs', ['page' => 2]))->assertSee('Program 7')->assertSee('Program 8')->assertDontSee('>Program 1<', false);
    $response = $this->get(route('programs', ['filter' => 'Selesai', 'q' => 'Program 8']))->assertOk()->assertSee('Program 8')->assertDontSee('>Program 1<', false);
    expect($response->viewData('items')->total())->toBe(1);
});

it('features the first announcement and shows later pages without duplicates', function (): void {
    foreach (range(1, 7) as $number) {
        PublicContent::create(['section' => 'announcements', 'slug' => 'berita-'.$number, 'position' => $number, 'is_published' => true,
            'payload' => ['title' => 'Berita '.$number, 'summary' => 'Kabar terbaru', 'category' => $number === 7 ? 'Akademik' : 'Info', 'date' => '01 November 2026', 'image' => 'placeholder.svg']]);
    }
    $this->get(route('announcements'))->assertOk()->assertSee('Berita 1')->assertSee('Berita 6')->assertDontSee('Berita 7');
    $this->get(route('announcements', ['page' => 2]))->assertSee('Berita 7')->assertDontSee('>Berita 1<', false);
    $this->get(route('announcements', ['filter' => 'Akademik']))->assertSee('Berita 7')->assertDontSee('>Berita 1<', false);
});

it('serves all redesigned public pages with the organization favicon', function (string $route): void {
    $this->get(route($route))->assertOk()->assertSee('rel="icon"', false)->assertSee('/images/himsi/logo-himsi.png', false);
})->with(['home', 'profile', 'structure', 'programs', 'announcements', 'achievements', 'registration.create']);

it('requires a photo for a new hero slide', function (): void {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($admin)->post(route('admin.content.store', 'hero_slides'), [
        'data' => ['title' => 'Slide kosong'], 'position' => 0, 'is_published' => 1,
    ])->assertSessionHasErrors('uploads.image');
    $this->assertDatabaseMissing('public_contents', ['section' => 'hero_slides']);
});

it('updates stored vision and all five missions without replacing other public settings', function (): void {
    $settings = PublicContent::create(['section' => 'settings', 'slug' => 'site', 'position' => 0, 'is_published' => true,
        'payload' => ['vision' => 'Visi lama', 'missions' => ['Misi lama'], 'logo' => 'logo-himsi.png', 'hero_badge' => 'Tetap tersimpan']]);
    $migration = require database_path('migrations/2026_11_09_132555_update_public_vision_and_missions.php');
    $migration->up();
    $settings->refresh();
    expect($settings->payload['vision'])->toStartWith('Menjadikan HIMSI FT-UNSULBAR');
    expect($settings->payload['missions'])->toHaveCount(5);
    expect($settings->payload['hero_badge'])->toBe('Tetap tersimpan');
    $this->get(route('profile'))->assertSee('budaya KMFT FT-UNSULBAR')->assertDontSee('Visi lama');
});
