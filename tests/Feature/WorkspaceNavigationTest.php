<?php

use App\Models\NotifikasiHimsi;
use App\Models\User;
use App\Support\Appearance;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

it('keeps personal notifications in the header and outside the sidebar for every role', function (string $role) {
    Storage::fake('local');
    Appearance::remember(['sidebar_background' => 'storage/himsi/sidebar-test.webp']);
    $this->withoutVite();
    $user = User::factory()->create(['role' => $role, 'department' => 'humas', 'is_active' => true]);
    $other = User::factory()->create();
    NotifikasiHimsi::kirim($user->id, 'Pembaruan milik saya', 'Pengajuan Anda sudah diperiksa.');
    NotifikasiHimsi::kirim($other->id, 'Pembaruan akun lain', 'Pesan privat akun lain.');

    $response = $this->actingAs($user)->get(route('administration.notifications'));
    $response->assertOk()->assertSee('Pembaruan milik saya')->assertDontSee('Pembaruan akun lain');
    $response->assertSee(asset('storage/himsi/sidebar-test.webp'), false);
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//header//a[contains(@aria-label, "Notifikasi, 1 belum dibaca")]')->length)->toBe(1);
    expect($xpath->query('//aside//a[contains(@href, "notifikasi")]')->length)->toBe(0);
    expect($xpath->query('//aside//nav//a')[0]->textContent)->toContain('Dashboard');
})->with(['super_admin', 'ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen', 'panitia_cb', 'panitia_kpum', 'kbh', 'koordinator_pengawas']);

it('requires authentication to open workspace notifications', function () {
    $this->get(route('administration.notifications'))->assertRedirect(route('login'));
});
