<?php

use App\Models\HimsiRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function kbhImportFixture(): string
{
    Storage::fake('local');
    Storage::disk('local')->put('import-test.csv', "name,nim,email,phone,password\nAnggota Uji,D0424999,anggota@example.com,0812-3456-7999,Example#2026\n");

    return Storage::disk('local')->path('import-test.csv');
}

it('imports an active KBH account with approved registration and hashed password without CB verification', function () {
    $path = kbhImportFixture();
    $this->artisan('himsi:import-kbh', ['file' => $path])->expectsOutput('Selesai: 1 dibuat, 0 diperbarui, 0 dilewati.')->assertSuccessful();
    $user = User::where('email', 'anggota@example.com')->firstOrFail();
    expect(Hash::check('Example#2026', $user->password))->toBeTrue();
    expect($user->awaitingRegistration())->toBeFalse();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'kbh', 'is_active' => true, 'phone' => '081234567999']);
    $this->assertDatabaseHas('himsi_registrations', ['user_id' => $user->id, 'nim' => 'D0424999', 'angkatan' => 2024, 'status' => 'approved']);
    $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'Example#2026'])->assertRedirect(route('role.welcome'));
    $this->artisan('himsi:import-kbh', ['file' => $path])->expectsOutput('Selesai: 0 dibuat, 0 diperbarui, 1 dilewati.')->assertSuccessful();
    $this->assertDatabaseCount('users', 1);
    Storage::disk('local')->assertExists('import-test.csv');
});

it('only updates a matching existing pending account when explicitly requested', function () {
    $path = kbhImportFixture();
    $user = User::factory()->create(['email' => 'anggota@example.com', 'role' => 'pending', 'is_active' => false]);
    HimsiRegistration::factory()->create(['user_id' => $user->id, 'nim' => 'D0424999', 'status' => 'pending', 'certificate_path' => 'existing.pdf']);
    $this->artisan('himsi:import-kbh', ['file' => $path])->assertSuccessful();
    expect($user->fresh()->role)->toBe('pending');
    $this->artisan('himsi:import-kbh', ['file' => $path, '--update-existing' => true])->expectsOutput('Selesai: 0 dibuat, 1 diperbarui, 0 dilewati.')->assertSuccessful();
    expect($user->fresh()->role)->toBe('kbh');
    expect(Hash::check('Example#2026', $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseHas('himsi_registrations', ['user_id' => $user->id, 'status' => 'approved', 'certificate_path' => 'existing.pdf']);
    Storage::disk('local')->assertExists('import-test.csv');
});

it('preserves officer roles and passwords even with the update option', function () {
    $path = kbhImportFixture();
    $user = User::factory()->create(['email' => 'anggota@example.com', 'role' => 'ketua_umum', 'is_active' => true]);
    $password = $user->password;
    $this->artisan('himsi:import-kbh', ['file' => $path, '--update-existing' => true])->expectsOutput('Selesai: 0 dibuat, 0 diperbarui, 1 dilewati.')->assertSuccessful();
    expect($user->fresh()->role)->toBe('ketua_umum');
    expect($user->fresh()->password)->toBe($password);
    $this->assertDatabaseCount('himsi_registrations', 0);
    Storage::disk('local')->assertExists('import-test.csv');
});

it('skips NIM email conflicts and validates all rows before importing', function () {
    $path = kbhImportFixture();
    $registration = HimsiRegistration::factory()->create(['nim' => 'D0424999']);
    $this->artisan('himsi:import-kbh', ['file' => $path, '--update-existing' => true])->expectsOutput('Selesai: 0 dibuat, 0 diperbarui, 1 dilewati.')->assertSuccessful();
    $this->assertDatabaseMissing('users', ['email' => 'anggota@example.com']);
    $this->assertDatabaseHas('himsi_registrations', ['id' => $registration->id, 'user_id' => $registration->user_id]);
    Storage::disk('local')->append('import-test.csv', 'Invalid,D0424888,invalid-email,081234567888,Example#2026');
    $this->artisan('himsi:import-kbh', ['file' => $path])->assertFailed();
    $this->assertDatabaseCount('users', 1);
    Storage::disk('local')->assertExists('import-test.csv');
});
