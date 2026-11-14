<?php

use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\OpsTask;
use App\Models\PublicContent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    $this->withoutVite();
});

function portalMember(array $attributes = []): User
{
    return User::factory()->create(array_replace(['role' => 'anggota_departemen', 'department' => 'kaderisasi', 'is_active' => true], $attributes));
}

function portalAnnouncement(string $title, ?string $department = null, bool $published = true): PublicContent
{
    return PublicContent::create(['section' => 'announcements', 'slug' => fake()->unique()->slug(), 'department' => $department, 'position' => 0, 'is_published' => $published, 'payload' => ['title' => $title, 'category' => 'Penting', 'summary' => $title, 'date' => '22 Oktober 2026', 'body' => ['Isi pengumuman.']]]);
}

it('redirects members to their dashboard and counts only their assignments', function () {
    $member = portalMember();
    $program = OpsProgram::factory()->create(['status' => 'running']);
    OpsTask::factory()->create(['assigned_to' => $member->id, 'program_id' => $program->id, 'title' => 'Tugas pribadi']);
    OpsTask::factory()->create(['program_id' => $program->id, 'title' => 'Tugas orang lain']);

    $this->actingAs($member)->get(route('dashboard'))->assertRedirect(route('department.member.dashboard', 'kaderisasi'));
    $response = $this->get(route('department.member.dashboard', 'kaderisasi'))->assertSee('Tugas pribadi')->assertDontSee('Tugas orang lain');
    expect($response->viewData('stats'))->toBe(['Tugas Aktif' => 1, 'Menunggu Review' => 0, 'Tugas Selesai' => 0, 'Program Berjalan' => 1]);
});

it('renders member navigation and empty states', function (string $route) {
    $response = $this->actingAs(portalMember())->get(route($route, 'kaderisasi'))->assertOk()->assertSee('PORTAL ANGGOTA')->assertSee('Program Kerja')->assertDontSee('Distribusi Tugas');
    preg_match('/<nav aria-label="Navigasi departemen">(.*?)<\/nav>/s', $response->getContent(), $navigation);
    expect($navigation[1])->not->toContain('Pengumuman')->not->toContain('Iuran Anggota');
})->with(['department.member.dashboard', 'department.member.programs', 'department.my-tasks', 'department.forum', 'department.member.announcements', 'department.member.notifications']);

it('requires authentication and an active account in the correct department', function () {
    $url = route('department.member.dashboard', 'kaderisasi');
    $this->get($url)->assertRedirect(route('login'));
    $this->actingAs(portalMember(['department' => 'humas']))->get($url)->assertForbidden();
    $this->actingAs(portalMember(['role' => 'kbh']))->get($url)->assertForbidden();
    $this->actingAs(portalMember(['is_active' => false]))->get($url)->assertRedirect(route('login'));
});

it('shows department programs but hides drafts and other members task results', function () {
    $member = portalMember();
    $program = OpsProgram::factory()->create(['status' => 'running', 'title' => 'Program terlihat']);
    $draft = OpsProgram::factory()->create(['title' => 'Draft rahasia']);
    $other = OpsProgram::factory()->create(['department' => 'humas', 'status' => 'running', 'title' => 'Program Humas']);
    OpsTask::factory()->create(['program_id' => $program->id, 'assigned_to' => $member->id, 'title' => 'Tugas saya']);
    $private = OpsTask::factory()->create(['program_id' => $program->id, 'title' => 'Tugas anggota lain', 'result' => 'Hasil privat']);

    $this->actingAs($member)->get(route('department.member.programs', 'kaderisasi'))->assertSee('Program terlihat')->assertDontSee('Draft rahasia')->assertDontSee('Program Humas');
    $this->get(route('department.member.program', ['kaderisasi', $program->id]))->assertSee('Tugas saya')->assertDontSee('Tugas anggota lain')->assertDontSee('Hasil privat');
    $this->get(route('department.member.program', ['kaderisasi', $draft->id]))->assertNotFound();
    $this->get(route('department.member.program', ['kaderisasi', $other->id]))->assertNotFound();
    $this->get(route('department.my-tasks', ['kaderisasi', 'task' => $private->id]))->assertNotFound();
    $this->get(route('department.program.create', 'kaderisasi'))->assertForbidden();
});

it('connects assignment progress submission revision and approval using shared tasks and notifications', function () {
    $this->freezeTime();
    $member = portalMember();
    $coordinator = portalMember(['role' => 'koordinator_departemen']);
    $program = OpsProgram::factory()->create(['status' => 'running', 'created_by' => $coordinator->id, 'pic_id' => $coordinator->id]);
    $program->members()->attach($member);

    $this->actingAs($coordinator)->post(route('department.task.store', 'kaderisasi'), ['program_id' => $program->id, 'members' => [$member->id], 'title' => 'Pendataan peserta', 'description' => 'Siapkan daftar.', 'deadline' => now()->addDay()->toDateString()])->assertSessionHasNoErrors();
    $task = OpsTask::firstOrFail();
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $member->id, 'judul' => 'Tugas baru']);
    $this->actingAs($member)->post(route('department.task.submit', ['kaderisasi', $task->id]), ['action' => 'progress', 'version' => 0, 'progress' => 45, 'result' => 'Sebagian peserta tercatat', 'status' => 'done'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('ops_tasks', ['id' => $task->id, 'progress' => 45, 'status' => 'working', 'version' => 1]);
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $coordinator->id, 'judul' => 'Progres tugas diperbarui']);
    $this->actingAs($coordinator)->get(route('department.monitoring', ['kaderisasi', 'task' => $task->id]))->assertSee('45%')->assertSee('Sebagian peserta tercatat');
    $this->actingAs($member)->post(route('department.task.submit', ['kaderisasi', $task->id]), ['action' => 'submit', 'version' => 1, 'result' => 'Daftar lengkap'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('ops_tasks', ['id' => $task->id, 'status' => 'submitted', 'progress' => 100, 'completed_at' => null]);
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $coordinator->id, 'judul' => 'Tugas menunggu review']);
    $this->actingAs($coordinator)->post(route('department.task.review', ['kaderisasi', $task->id]), ['decision' => 'revision', 'version' => 2, 'feedback' => 'Tambahkan NIM'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $member->id, 'judul' => 'Tugas perlu revisi']);
    $this->actingAs($member)->post(route('department.task.submit', ['kaderisasi', $task->id]), ['action' => 'submit', 'version' => 3, 'result' => 'Daftar dan NIM lengkap'])->assertSessionHasNoErrors();
    $this->actingAs($coordinator)->post(route('department.task.review', ['kaderisasi', $task->id]), ['decision' => 'done', 'version' => 4, 'feedback' => 'Sesuai'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('ops_tasks', ['id' => $task->id, 'status' => 'done', 'progress' => 100, 'completed_at' => now()->toDateTimeString()]);
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $member->id, 'judul' => 'Tugas selesai divalidasi']);
});

it('rejects invalid progress without updates or notifications', function (mixed $progress) {
    $member = portalMember();
    $task = OpsTask::factory()->create(['program_id' => OpsProgram::factory()->create(['status' => 'running'])->id, 'assigned_to' => $member->id]);

    $this->actingAs($member)->post(route('department.task.submit', ['kaderisasi', $task->id]), ['action' => 'progress', 'version' => 0, 'progress' => $progress])->assertSessionHasErrors('progress');
    $this->assertDatabaseHas('ops_tasks', ['id' => $task->id, 'progress' => 0, 'version' => 0]);
    $this->assertDatabaseCount('notifikasi_himsis', 0);
})->with([-1, 101, 'abc', null, 10.5]);

it('rejects stale progress and changes to tasks awaiting review', function () {
    $member = portalMember();
    $task = OpsTask::factory()->create(['program_id' => OpsProgram::factory()->create(['status' => 'running'])->id, 'assigned_to' => $member->id, 'status' => 'submitted', 'version' => 1]);
    $url = route('department.task.submit', ['kaderisasi', $task->id]);

    $this->actingAs($member)->post($url, ['action' => 'progress', 'version' => 0, 'progress' => 40])->assertSessionHasErrors('version');
    $this->post($url, ['action' => 'progress', 'version' => 1, 'progress' => 40])->assertSessionHasErrors('workflow');
    $this->assertDatabaseHas('ops_tasks', ['id' => $task->id, 'version' => 1, 'status' => 'submitted']);
    $this->assertDatabaseCount('notifikasi_himsis', 0);
});

it('scopes announcements and records one read per account', function () {
    $member = portalMember();
    portalAnnouncement('Pengumuman umum');
    $internal = portalAnnouncement('Internal Kaderisasi', 'kaderisasi');
    $other = portalAnnouncement('Internal Humas', 'humas');
    $draft = portalAnnouncement('Draft belum terbit', 'kaderisasi', false);

    $this->actingAs($member)->get(route('department.member.announcements', 'kaderisasi'))->assertSee('Pengumuman umum')->assertSee('Internal Kaderisasi')->assertDontSee('Internal Humas')->assertDontSee('Draft belum terbit');
    $this->get(route('department.member.announcement', ['kaderisasi', $other->id]))->assertNotFound();
    $this->get(route('department.member.announcement', ['kaderisasi', $draft->id]))->assertNotFound();
    $this->get(route('department.member.announcement', ['kaderisasi', $internal->id]))->assertSee('Internal Kaderisasi');
    $this->get(route('department.member.announcement', ['kaderisasi', $internal->id]))->assertOk();
    $this->assertDatabaseCount('announcement_reads', 1);
    $this->assertDatabaseHas('announcement_reads', ['content_id' => $internal->id, 'user_id' => $member->id]);
    expect(array_column(PublicContent::items('announcements'), 'title'))->toBe(['Pengumuman umum']);
    $this->get(route('announcements'))->assertDontSee('Internal Kaderisasi')->assertDontSee('Internal Humas');
    $this->get(route('announcement.detail', $internal->slug))->assertNotFound();
});

it('filters announcements and escapes their content', function () {
    $announcement = portalAnnouncement('<script>alert(1)</script>', 'kaderisasi');
    portalAnnouncement('Pengumuman lainnya');

    $this->actingAs(portalMember())->get(route('department.member.announcements', ['kaderisasi', 'q' => 'alert', 'category' => 'Penting']))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Pengumuman lainnya');
    $this->get(route('department.member.announcement', ['kaderisasi', $announcement->id]))->assertDontSee('<script>alert(1)</script>', false);
});

it('keeps notification reads and deletion private to the signed in user', function () {
    $member = portalMember();
    $other = portalMember();
    NotifikasiHimsi::kirim($member->id, 'Notifikasi pribadi', 'Tugas baru', route('department.my-tasks', 'kaderisasi', false), 'tugas');
    NotifikasiHimsi::kirim($other->id, 'Notifikasi orang lain', 'Privat');
    $mine = NotifikasiHimsi::where('user_id', $member->id)->firstOrFail();
    $theirs = NotifikasiHimsi::where('user_id', $other->id)->firstOrFail();

    $this->actingAs($member)->get(route('department.member.notifications', 'kaderisasi'))->assertSee('Notifikasi pribadi')->assertDontSee('Notifikasi orang lain');
    $this->post(route('notifikasi-himsi.baca', $theirs))->assertForbidden();
    $this->post(route('notifikasi-himsi.baca', $mine))->assertRedirect(route('department.my-tasks', 'kaderisasi', false));
    expect($mine->fresh()->dibaca_pada)->not->toBeNull();
    $this->post(route('department.member.notifications.read', 'kaderisasi'))->assertSessionHasNoErrors();
    expect($theirs->fresh()->dibaca_pada)->toBeNull();
    $this->delete(route('department.member.notifications.destroy', 'kaderisasi'))->assertSessionHasNoErrors();
    $this->assertModelMissing($mine);
    $this->assertModelExists($theirs);
});

it('opens all super admin member portals with shared tasks and no member submission form', function (string $department) {
    $admin = portalMember(['role' => 'super_admin']);
    $program = OpsProgram::factory()->create(['department' => $department, 'status' => 'running']);
    $task = OpsTask::factory()->create(['program_id' => $program->id, 'title' => 'Tugas dalam pratinjau']);

    $this->actingAs($admin)->get(route('admin.module', ['role' => 'anggota_departemen', 'department' => $department]))->assertSee(route('department.member.dashboard', $department));
    $this->get(route('department.member.dashboard', $department))->assertSee('Tugas dalam pratinjau')->assertSee('Pratinjau Super Admin');
    $this->get(route('department.my-tasks', [$department, 'task' => $task->id]))->assertSee('Tugas dalam pratinjau')->assertDontSee('Kirim untuk Review');
    $this->post(route('department.task.submit', [$department, $task->id]), ['action' => 'progress', 'version' => 0, 'progress' => 100])->assertNotFound();
    $this->assertDatabaseHas('ops_tasks', ['id' => $task->id, 'progress' => 0]);
})->with(['akademik', 'humas', 'advokasi', 'kaderisasi', 'dana_usaha', 'kesekretariatan']);

it('publishes targeted announcements through the existing super admin editor', function () {
    $admin = portalMember(['role' => 'super_admin']);
    $data = ['slug' => 'rapat-anggota', 'position' => 0, 'is_published' => 1, 'department' => 'kaderisasi', 'data' => ['title' => 'Rapat anggota', 'category' => 'Iuran', 'date' => '2026-10-22', 'summary' => 'Informasi rapat', 'body' => 'Isi rapat']];

    $this->actingAs($admin)->post(route('admin.content.store', 'announcements'), $data)->assertSessionHasNoErrors();
    $announcement = PublicContent::where('slug', 'rapat-anggota')->firstOrFail();
    $this->assertDatabaseHas('public_contents', ['id' => $announcement->id, 'department' => 'kaderisasi', 'is_published' => 1]);
    $this->actingAs(portalMember())->get(route('department.member.announcements', 'kaderisasi'))->assertSee('Rapat anggota');
    $this->actingAs($admin)->post(route('admin.content.store', 'announcements'), array_replace($data, ['slug' => 'invalid', 'department' => 'tidak-ada']))->assertSessionHasErrors('department');
    $this->assertDatabaseMissing('public_contents', ['slug' => 'invalid']);
});
