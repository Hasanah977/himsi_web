<?php

use App\Models\HimsiRegistration;
use App\Models\KpumElection;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\OpsTask;
use App\Models\PengawasanEskalasi;
use App\Models\PengawasanLaporan;
use App\Models\PengawasanRekomendasi;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('renders the five redesigned dashboards through the active role routes', function (string $role, string $route, string $marker, array $parameters) {
    $user = User::factory()->create(['role' => $role, 'department' => $role === 'anggota_departemen' ? 'kaderisasi' : null, 'is_active' => true]);
    $this->actingAs($user)->get(route($route, $parameters))->assertOk()
        ->assertSee('data-dashboard="'.$marker.'"', false)
        ->assertSee('himsi-dashboard-panels.css')
        ->assertDontSee('Buka Pusat Persetujuan')
        ->assertDontSee('Status Data Pemilih')
        ->assertDontSee('Aktivitas Terbaru');
})->with([
    ['ketua_umum', 'ketua-umum.dashboard', 'chair', []],
    ['koordinator_pengawas', 'pengawas.dashboard', 'supervisor', []],
    ['panitia_kpum', 'kpum.dashboard', 'kpum', []],
    ['panitia_cb', 'cb.dashboard', 'cb', []],
    ['anggota_departemen', 'department.member.dashboard', 'department-member', ['kaderisasi']],
]);

it('counts only the members own tasks and excludes submitted and done tasks from overdue', function () {
    $user = User::factory()->create(['role' => 'anggota_departemen', 'department' => 'kaderisasi', 'is_active' => true]);
    $program = OpsProgram::factory()->create(['status' => 'running']);
    foreach (['working', 'submitted', 'done'] as $status) {
        OpsTask::factory()->create(['program_id' => $program->id, 'assigned_to' => $user->id, 'status' => $status, 'deadline' => today()->subDays(2)]);
    }
    OpsTask::factory()->create(['program_id' => $program->id, 'title' => 'Tugas milik orang lain']);
    $this->actingAs($user)->get(route('department.member.dashboard', 'kaderisasi'))->assertOk()
        ->assertViewHas('totalTasks', 3)->assertViewHas('overdueTasks', 1)
        ->assertViewHas('taskCounts', fn ($counts) => $counts['done'] === 1 && $counts['working'] === 1)
        ->assertDontSee('Tugas milik orang lain');
});

it('derives CB review and document completeness from registrations', function () {
    $user = User::factory()->create(['role' => 'panitia_cb', 'is_active' => true]);
    HimsiRegistration::factory()->create(['status' => 'pending', 'student_card_path' => 'card.pdf', 'active_letter_path' => 'letter.pdf', 'certificate_path' => 'certificate.pdf']);
    HimsiRegistration::factory()->create(['status' => 'returned', 'certificate_path' => '']);
    $this->actingAs($user)->get(route('cb.dashboard'))->assertOk()->assertViewHas('total', 2)
        ->assertViewHas('completeDocuments', 1)
        ->assertViewHas('pendingRegistrations', fn ($items) => $items->count() === 1 && $items->first()->status === 'pending');
});

it('does not render election results before publication', function () {
    $user = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);
    KpumElection::findOrFail(1)->update(['status' => 'certified']);
    $this->actingAs($user)->get(route('kpum.dashboard'))->assertOk()
        ->assertDontSee('class="rd-results"', false)->assertSee('Partisipasi Pemilih');
    KpumElection::findOrFail(1)->update(['status' => 'published']);
    $this->get(route('kpum.dashboard'))->assertOk()->assertSee('Hasil Pemilihan');
});

it('counts chair decisions without including already reviewed PPRK submissions', function () {
    $user = User::factory()->create(['role' => 'ketua_umum', 'is_active' => true]);
    OpsSubmission::factory()->create(['type' => 'report', 'status' => 'pending_chair']);
    OpsSubmission::factory()->create(['type' => 'rpk', 'status' => 'pending_officers', 'payload' => ['approvals' => ['ketua_umum' => ['user_id' => $user->id]]]]);
    $this->actingAs($user)->get(route('ketua-umum.dashboard'))->assertOk()
        ->assertViewHas('stats', fn ($stats) => $stats['Menunggu Keputusan'] === 1);
});

it('counts distinct monitored programs and active supervision records', function () {
    $user = User::factory()->create(['role' => 'koordinator_pengawas', 'is_active' => true]);
    $program = OpsProgram::factory()->create(['status' => 'completed']);
    $report = PengawasanLaporan::create(['ops_program_id' => $program->id, 'dibuat_oleh' => $user->id, 'judul' => 'Laporan pengujian', 'nama_kegiatan' => 'Kegiatan', 'isi' => 'Catatan pengawasan', 'status' => 'perlu_perbaikan']);
    PengawasanLaporan::create(['ops_program_id' => $program->id, 'dibuat_oleh' => $user->id, 'judul' => 'Laporan kedua', 'nama_kegiatan' => 'Kegiatan', 'isi' => 'Sudah ditinjau', 'status' => 'selesai']);
    PengawasanRekomendasi::create(['laporan_id' => $report->id, 'dibuat_oleh' => $user->id, 'rekomendasi' => 'Lengkapi dokumen', 'status' => 'terbuka']);
    PengawasanEskalasi::create(['laporan_id' => $report->id, 'diajukan_oleh' => $user->id, 'alasan' => 'Perlu keputusan', 'status' => 'menunggu']);
    $this->actingAs($user)->get(route('pengawas.dashboard'))->assertOk()
        ->assertViewHas('monitored', 1)->assertViewHas('needsAttention', 1)->assertViewHas('completedPrograms', 1)
        ->assertViewHas('stats', fn ($stats) => $stats['Rekomendasi Aktif'] === 1 && $stats['Eskalasi Menunggu Keputusan'] === 1)
        ->assertSee('Lengkapi dokumen');
});
