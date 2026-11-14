<?php

use App\Models\PublicContent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('keeps the main quote and shows only published public additional quotes in order', function (): void {
    PublicContent::create([
        'section' => 'settings', 'slug' => 'site', 'is_published' => true,
        'payload' => ['quote' => 'Kutipan utama tetap tampil', 'quote_name' => 'Pengurus utama'],
    ]);
    foreach ([['Kutipan kedua', 2, true, null], ['Kutipan pertama', 1, true, null], ['Kutipan draft', 0, false, null], ['Kutipan internal', 0, true, 'humas']] as [$quote, $position, $published, $department]) {
        PublicContent::create([
            'section' => 'quotes', 'position' => $position, 'is_published' => $published, 'department' => $department,
            'payload' => ['quote' => $quote, 'name' => '<script>Nama</script>', 'role' => 'Pengurus HIMSI', 'image' => 'placeholder.svg'],
        ]);
    }

    $response = $this->get(route('home'));

    $response->assertSeeInOrder(['Kutipan utama tetap tampil', 'Kutipan pertama', 'Kutipan kedua'])
        ->assertDontSee('Kutipan draft')->assertDontSee('Kutipan internal')
        ->assertSee('<script>Nama</script>')->assertDontSee('<script>Nama</script>', false);
});

it('allows super admin to publish an additional quote without a photo', function (): void {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

    $response = $this->actingAs($admin)->post(route('admin.content.store', 'quotes'), [
        'position' => 1, 'is_published' => 1,
        'data' => ['quote' => 'Belajar dan bertumbuh bersama.', 'name' => 'Pengurus HIMSI', 'role' => 'Ketua Umum'],
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('public_contents', ['section' => 'quotes', 'is_published' => true]);
    $this->get(route('home'))->assertSee('Belajar dan bertumbuh bersama.');
});

it('does not allow a member to publish an additional quote', function (): void {
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);

    $response = $this->actingAs($member)->post(route('admin.content.store', 'quotes'), [
        'position' => 1, 'is_published' => 1,
        'data' => ['quote' => 'Tidak boleh dipublikasikan.', 'name' => 'Anggota', 'role' => 'KBH'],
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('public_contents', ['section' => 'quotes']);
});
