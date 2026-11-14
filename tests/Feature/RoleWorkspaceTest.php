<?php

use App\Models\OfficerReport;
use App\Models\OpsSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('keeps one role sidebar across dashboard operational reviews and LPJ', function (string $role, string $dashboard, string $menuLabel) {
    $user = User::factory()->create(['role' => $role, 'is_active' => true]);
    $report = OfficerReport::factory()->for($user)->create(['role' => $role]);
    $submission = OpsSubmission::factory()->create(['type' => $role === 'bendahara_umum' ? 'budget' : 'report', 'status' => 'pending_supervisor']);
    foreach ([route($dashboard), route('department.reviews'), route('department.review', $submission), route('department.pprk'), route('officer-reports.index'), route('officer-reports.show', $report)] as $url) {
        $response = $this->actingAs($user)->get($url)->assertOk()->assertSee($menuLabel)->assertSee(route($dashboard))->assertSee(route('officer-reports.index'));
        expect(substr_count($response->getContent(), '<aside '))->toBe(1);
        $response->assertSee('id="administrationSidebar"', false)->assertDontSee('id="departmentSidebar"', false);
    }
})->with([
    ['ketua_umum', 'ketua-umum.dashboard', 'Monitoring Organisasi'],
    ['sekretaris_umum', 'sekretaris.dashboard', 'Surat Masuk'],
    ['bendahara_umum', 'finance.dashboard', 'Monitoring Anggaran'],
    ['koordinator_pengawas', 'pengawas.dashboard', 'Hasil Audit'],
]);

it('keeps the department sidebar on shared notifications and aspirations', function () {
    $user = User::factory()->create(['role' => 'koordinator_departemen', 'department' => 'humas', 'is_active' => true]);
    foreach (['department.pprk', 'administration.notifications', 'administration.aspirations'] as $route) {
        $this->actingAs($user)->get(route($route))->assertOk()->assertSee('id="departmentSidebar"', false)->assertSee('Distribusi Tugas')->assertSee('Monitoring Anggota')->assertDontSee('id="administrationSidebar"', false);
    }
});

it('shows a real zero state and statistics instead of quick access', function () {
    $secretary = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $this->actingAs($secretary)->get(route('sekretaris.dashboard'))->assertOk()->assertSee('Alur Persuratan')->assertSee('Belum ada surat masuk')->assertDontSee('Akses Cepat');
    $supervisor = User::factory()->create(['role' => 'koordinator_pengawas', 'is_active' => true]);
    $this->actingAs($supervisor)->get(route('pengawas.dashboard'))->assertOk()->assertSee('Status Laporan Pengawasan')->assertDontSee('Aksi Cepat');
});

it('shows department LPJs awaiting verification alongside officer LPJs', function () {
    $supervisor = User::factory()->create(['role' => 'koordinator_pengawas', 'is_active' => true]);
    OpsSubmission::factory()->create(['type' => 'report', 'status' => 'pending_supervisor']);
    OpsSubmission::factory()->create(['type' => 'report', 'status' => 'pending_chair']);
    $this->actingAs($supervisor)->get(route('officer-reports.index'))->assertOk()->assertSee('1 LPJ departemen menunggu verifikasi Anda.')->assertSee(route('department.reviews'));
});

it('preserves the selected super admin role preview on shared pages without granting approval rights', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $report = OfficerReport::factory()->create();
    $this->actingAs($admin)->get(route('ketua-umum.dashboard'))->assertOk()->assertSee('Monitoring Organisasi');
    foreach ([route('department.reviews'), route('department.pprk'), route('officer-reports.index'), route('officer-reports.show', $report)] as $url) {
        $this->get($url)->assertOk()->assertSee('Monitoring Organisasi')->assertSee('id="administrationSidebar"', false)->assertDontSee('id="departmentSidebar"', false);
    }
    $this->get(route('administration.notifications'))->assertOk()->assertSee('id="adminSidebar"', false)->assertSee('Kontrol Kepengurusan')->assertDontSee('Monitoring Organisasi');
    $this->post(route('officer-reports.review', $report), ['version' => 1, 'status' => 'approved', 'note' => 'Tidak berwenang'])->assertForbidden();
    $this->get(route('sekretaris.dashboard'))->assertOk();
    $this->get(route('department.reviews'))->assertSee('Surat Masuk')->assertDontSee('Monitoring Organisasi');
    $this->get(route('admin.dashboard'))->assertOk()->assertSessionMissing('administration_preview_role');
    expect($admin->fresh()->role)->toBe('super_admin');
});
