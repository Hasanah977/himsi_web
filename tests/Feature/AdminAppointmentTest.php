<?php

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    $this->withoutVite();
});

it('assigns one officer role to an existing KBH account and records its period and actor', function (string $role) {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $student = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $department = in_array($role, ['koordinator_departemen', 'anggota_departemen'], true) ? 'akademik' : null;
    $this->actingAs($admin)->put(route('admin.users.appointment', $student->encryptedAccountId()), [
        'appointment_role' => $role, 'appointment_department' => $department, 'period' => '2026/2027', 'reason' => 'Penggantian pengurus', 'expected_role' => 'kbh',
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect($student->fresh()->assignedRole())->toBe($role);
    expect($student->fresh()->canSwitchWorkspace())->toBeTrue();
    $event = SecurityEvent::where('event', 'account_appointment')->sole();
    expect($event->actor_id)->toBe($admin->id);
    expect($event->context)->toMatchArray(['account_id' => $student->id, 'period' => '2026/2027', 'reason' => 'Penggantian pengurus']);
    $this->get(route('admin.users.edit', $student->encryptedAccountId()))->assertOk()->assertSee('Kelola Jabatan')->assertSee('2026/2027');
    $this->assertDatabaseCount('users', 2);
})->with(User::OFFICER_ROLES);

it('changes and revokes appointments without deleting the account and refuses stale changes', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $student = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $url = route('admin.users.appointment', $student->encryptedAccountId());
    $data = ['appointment_role' => 'koordinator_departemen', 'appointment_department' => 'humas', 'expected_role' => 'sekretaris_umum', 'period' => '2026/2027', 'reason' => 'Pergantian jabatan'];
    $this->actingAs($admin)->put($url, $data)->assertSessionHasNoErrors();
    $this->put($url, $data)->assertStatus(409);
    $this->put($url, ['appointment_role' => 'kbh', 'expected_role' => 'koordinator_departemen', 'expected_department' => 'humas', 'period' => '2026/2027', 'reason' => 'Mengundurkan diri'])->assertSessionHasNoErrors();
    expect($student->fresh()->assignedRole())->toBe('kbh');
    expect($student->fresh()->department)->toBeNull();
    expect($student->fresh()->canSwitchWorkspace())->toBeFalse();
    $this->actingAs($student->fresh())->get(route('sekretaris.dashboard'))->assertForbidden();
    $this->post(route('workspace.update'), ['role' => 'sekretaris_umum'])->assertForbidden();
});

it('requires a reason period and department and forbids nonstudent appointments', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $student = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $url = route('admin.users.appointment', $student->encryptedAccountId());
    $this->actingAs($admin)->put($url, ['appointment_role' => 'anggota_departemen', 'expected_role' => 'kbh'])->assertSessionHasErrors(['period', 'reason', 'appointment_department']);
    $data = ['appointment_role' => 'super_admin', 'expected_role' => 'kbh', 'period' => '2026', 'reason' => 'Uji'];
    $this->put($url, $data)->assertSessionHasErrors('appointment_role');
    $data['appointment_role'] = 'ketua_umum';
    $this->put(route('admin.users.appointment', $admin->encryptedAccountId()), $data)->assertStatus(422);
    expect($student->fresh()->assignedRole())->toBe('kbh');
    expect(SecurityEvent::where('event', 'account_appointment')->count())->toBe(0);
});

it('deactivates and reactivates accounts without losing their appointments', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $student = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $url = route('admin.users.status', $student->encryptedAccountId());
    $this->actingAs($admin)->put($url, ['active' => 0, 'reason' => 'Permintaan mahasiswa'])->assertSessionHasNoErrors();
    expect($student->fresh()->is_active)->toBeFalse();
    expect($student->fresh()->assignedRole())->toBe('sekretaris_umum');
    $this->actingAs($student->fresh())->get(route('sekretaris.dashboard'))->assertRedirect(route('login'));
    $this->actingAs($admin)->put($url, ['active' => 1, 'reason' => 'Aktif kembali'])->assertSessionHasNoErrors();
    expect($student->fresh()->canSwitchWorkspace())->toBeTrue();
    expect(SecurityEvent::where('event', 'account_status')->count())->toBe(2);
    $this->delete(route('admin.users.destroy', $student->encryptedAccountId()))->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $student->id]);
    $this->put(route('admin.users.status', $admin->encryptedAccountId()), ['active' => 0, 'reason' => 'Uji'])->assertForbidden();
});

it('restricts access changes to super admins and rejects tampered identifiers', function () {
    $student = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $url = route('admin.users.appointment', $student->encryptedAccountId());
    $this->put($url, [])->assertRedirect(route('login'));
    $this->actingAs($student)->put($url, [])->assertForbidden();
    $this->put(route('admin.users.status', $student->encryptedAccountId()), [])->assertForbidden();
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($admin)->put(route('admin.users.appointment', 'broken'), [])->assertNotFound();
});

it('prevents bypassing appointment and status management through account editing', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $student = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $url = route('admin.users.update', $student->encryptedAccountId());
    $data = ['name' => $student->name, 'email' => $student->email, 'role' => 'sekretaris_umum', 'is_active' => 1];
    $this->actingAs($admin)->put($url, $data)->assertStatus(422);
    $data['role'] = 'kbh';
    $data['is_active'] = 0;
    $this->put($url, $data)->assertStatus(422);
    expect($student->fresh()->assignedRole())->toBe('kbh');
    expect($student->fresh()->is_active)->toBeTrue();
});
