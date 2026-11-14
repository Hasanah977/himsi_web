<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    $this->withoutVite();
});

it('finds accounts by partial name and email', function (string $search) {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $match = User::factory()->create(['name' => 'Nadia Putri', 'email' => 'nadia.himsi@example.test']);
    $other = User::factory()->create(['name' => 'Rizki Ahmad', 'email' => 'rizki@example.test']);
    $this->actingAs($admin)->get(route('admin.users.index', ['search' => $search]))
        ->assertOk()->assertViewHas('search', trim($search))
        ->assertViewHas('users', fn ($users) => $users->modelKeys() === [$match->id])
        ->assertSee($match->email)->assertDontSee($other->email);
})->with(['  NADIA  ', 'himsi@example']);

it('shows all accounts when cleared and a helpful message when nothing matches', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $other = User::factory()->create();
    $this->actingAs($admin)->get(route('admin.users.index'))
        ->assertOk()->assertViewHas('users', fn ($users) => $users->contains($admin) && $users->contains($other));
    $this->get(route('admin.users.index', ['search' => 'no-matching-account']))
        ->assertOk()->assertViewHas('users', fn ($users) => $users->isEmpty())
        ->assertSee('Tidak ada akun yang cocok.');
});

it('validates account search input and restricts access to super admins', function () {
    $this->get(route('admin.users.index', ['search' => 'nadia']))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))
        ->get(route('admin.users.index', ['search' => 'nadia']))->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
        ->get(route('admin.users.index', ['search' => ['invalid']]))->assertSessionHasErrors('search');
    $this->get(route('admin.users.index', ['search' => str_repeat('x', 256)]))->assertSessionHasErrors('search');
});
