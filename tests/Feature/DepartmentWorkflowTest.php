<?php

use App\Models\OpsGroup;
use App\Models\OpsMessage;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\OpsTask;
use App\Models\PprkSubmission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    $this->withoutVite();
});

function departmentUser(string $role = 'koordinator_departemen', string $department = 'kaderisasi'): User
{
    return User::factory()->create(['role' => $role, 'department' => $department, 'is_active' => true]);
}
function departmentProgramData(User $person, array $changes = []): array
{
    return array_replace(['title' => 'Workshop Sistem Informasi', 'description' => 'Pelatihan untuk mahasiswa.', 'period' => '2026/2027', 'starts_on' => '2026-10-20', 'ends_on' => '2026-10-30', 'location' => 'Kampus', 'target' => 'Mahasiswa SI', 'pic_id' => $person->id, 'members' => [$person->id], 'version' => 0], $changes);
}
function departmentLetter(array $changes = []): array
{
    return array_replace(['file' => UploadedFile::fake()->create('proposal.pdf', 10, 'application/pdf'), 'recipient' => 'Sekertaris Umum', 'subject' => 'Permohonan kegiatan', 'body' => 'Mohon memproses surat kegiatan.', 'version' => 0, 'action' => 'submit'], $changes);
}

it('renders every coordinator list and creation screen', function (string $name, array $args) {
    PprkSubmission::factory()->approved()->create();
    $this->actingAs(departmentUser())->get(route($name, ['kaderisasi', ...$args]))->assertOk();
})->with([
    ['department.dashboard', []], ['department.programs', []], ['department.program.create', []],
    ['department.submissions', ['letter']], ['department.submissions', ['budget']], ['department.submissions', ['report']],
    ['department.tasks', []], ['department.task.create', []], ['department.monitoring', []], ['department.forum', []],
]);

it('requires active authenticated accounts and isolates departments', function () {
    $this->get(route('department.dashboard', 'kaderisasi'))->assertRedirect(route('login'));
    $user = departmentUser();
    $this->actingAs($user)->get(route('department.dashboard', 'humas'))->assertForbidden();
    $p = OpsProgram::factory()->create(['department' => 'humas', 'title' => 'RAHASIA HUMAS']);
    $this->get(route('department.program.show', ['kaderisasi', $p->id]))->assertNotFound();
    $this->get(route('department.programs', 'kaderisasi'))->assertDontSee('RAHASIA HUMAS');
    $this->actingAs(departmentUser('anggota_departemen'))->get(route('department.program.create', 'kaderisasi'))->assertForbidden();
    $user->forceFill(['is_active' => false])->save();
    $this->actingAs($user)->get(route('department.dashboard', 'kaderisasi'))->assertRedirect(route('login'));
});

it('redirects accounts to their correct departmental workspace', function () {
    PprkSubmission::factory()->approved()->create();
    $this->actingAs(departmentUser())->get(route('dashboard'))->assertRedirect(route('department.dashboard', 'kaderisasi'));
    $this->actingAs(departmentUser('anggota_departemen'))->get(route('dashboard'))->assertRedirect(route('department.member.dashboard', 'kaderisasi'));
});

it('saves draft programs with real department members and rejects injected approval', function () {
    PprkSubmission::factory()->approved()->create();
    $user = departmentUser();
    $member = departmentUser('anggota_departemen');
    $this->actingAs($user)->post(route('department.program.store', 'kaderisasi'), departmentProgramData($user, ['members' => [$member->id], 'status' => 'approved', 'department' => 'humas']))->assertSessionHasNoErrors();
    $p = OpsProgram::firstOrFail();
    expect($p->department)->toBe('kaderisasi');
    expect($p->status)->toBe('draft');
    expect($p->members()->count())->toBe(2);
    $this->get(route('department.program.show', ['kaderisasi', $p->id]))->assertOk();
    $this->get(route('department.program.edit', ['kaderisasi', $p->id]))->assertOk();
    foreach (['letter', 'budget', 'report'] as $type) {
        $this->get(route('department.submission.edit', ['kaderisasi', $p->id, $type]))->assertOk();
    }
    $other = departmentUser('anggota_departemen', 'humas');
    $this->post(route('department.program.store', 'kaderisasi'), departmentProgramData($user, ['members' => [$other->id]]))->assertSessionHasErrors('members.0');
    $this->post(route('department.program.store', 'kaderisasi'), departmentProgramData($user, ['ends_on' => '2026-10-19']))->assertSessionHasErrors('ends_on');
});

it('keeps approval sequential and records explicit secretary and chair actions', function () {
    Storage::fake('local');
    $user = departmentUser();
    $program = OpsProgram::factory()->create(['pic_id' => $user->id, 'created_by' => $user->id]);
    $this->actingAs($user)->post(route('department.submission.save', ['kaderisasi', $program->id, 'letter']), departmentLetter())->assertSessionHasNoErrors();
    $s = OpsSubmission::firstOrFail();
    expect($s->status)->toBe('pending_secretary');
    expect($program->fresh()->status)->toBe('pending');
    $this->post(route('department.review.save', $s->id), ['action' => 'approve', 'version' => 1, 'note' => 'Setuju'])->assertForbidden();
    $this->actingAs(departmentUser('ketua_umum'))->post(route('department.review.save', $s->id), ['action' => 'approve', 'version' => 1, 'note' => 'Setuju'])->assertForbidden();
    $secretary = departmentUser('sekretaris_umum');
    $this->actingAs($secretary)->get(route('department.review', $s->id))->assertOk();
    $this->post(route('department.review.save', $s->id), ['action' => 'forward', 'version' => 1, 'note' => 'Sudah diperiksa', 'number' => '01/HIMSI/X/2026', 'file' => UploadedFile::fake()->create('surat.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    expect($s->fresh()->status)->toBe('pending_chair');
    Storage::disk('local')->assertExists($s->fresh()->file_path);
    $this->actingAs(departmentUser('ketua_umum'))->post(route('department.review.save', $s->id), ['action' => 'approve', 'version' => 2, 'note' => 'Kegiatan disetujui'])->assertSessionHasNoErrors();
    expect($s->fresh()->status)->toBe('approved');
    expect($program->fresh()->status)->toBe('approved');
    $this->actingAs($secretary)->post(route('department.review.save', $s->id), ['action' => 'sent', 'version' => 3, 'note' => 'Surat sudah disampaikan'])->assertSessionHasNoErrors();
    expect($s->fresh()->status)->toBe('sent');
    expect(DB::table('ops_history')->count())->toBe(4);
});

it('locks submitted content and detects stale edits', function () {
    $user = departmentUser();
    $p = OpsProgram::factory()->create(['status' => 'pending', 'version' => 2]);
    $this->actingAs($user)->put(route('department.program.update', ['kaderisasi', $p->id]), departmentProgramData($user, ['version' => 2]))->assertSessionHasErrors('workflow');
    $p->update(['status' => 'draft']);
    $this->put(route('department.program.update', ['kaderisasi', $p->id]), departmentProgramData($user, ['version' => 1]))->assertSessionHasErrors('version');
});

it('gates uploaded budgets and records their declared totals without generating expenses', function () {
    Storage::fake('local');
    $p = OpsProgram::factory()->create();
    $user = departmentUser();
    $data = ['version' => 0, 'action' => 'submit', 'total' => 95000, 'file' => UploadedFile::fake()->create('rab.pdf', 10, 'application/pdf'), 'items' => [['name' => 'Konsumsi', 'qty' => 3, 'unit' => 'paket', 'price' => 25000], ['name' => 'ATK', 'qty' => 2, 'unit' => 'paket', 'price' => 10000]]];
    $this->actingAs($user)->post(route('department.submission.save', ['kaderisasi', $p->id, 'budget']), $data)->assertSessionHasErrors('workflow');
    $p->update(['status' => 'approved']);
    $this->post(route('department.submission.save', ['kaderisasi', $p->id, 'budget']), $data)->assertSessionHasNoErrors();
    $s = OpsSubmission::where('type', 'budget')->firstOrFail();
    expect($s->total)->toBe(95000);
    expect($s->status)->toBe('pending_treasurer');
    $this->get(route('department.submissions', ['kaderisasi', 'budget', 'tab' => 'expenses']))->assertOk()->assertDontSee('Data pengeluaran belum terhubung');
    $this->actingAs(departmentUser('bendahara_umum'))->post(route('department.review.save', $s->id), ['version' => 1, 'action' => 'revision', 'note' => 'Kurangi kebutuhan ATK'])->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('department.submission.save', ['kaderisasi', $p->id, 'budget']), array_replace($data, ['version' => 2, 'file' => UploadedFile::fake()->create('rab-revisi.pdf', 10, 'application/pdf')]))->assertSessionHasNoErrors();
    $this->actingAs(departmentUser('bendahara_umum'))->post(route('department.review.save', $s->id), ['version' => 3, 'action' => 'forward', 'note' => 'RAB lengkap'])->assertSessionHasNoErrors();
    $this->actingAs(departmentUser('ketua_umum'))->post(route('department.review.save', $s->id), ['version' => 4, 'action' => 'approve', 'note' => 'RAB disetujui'])->assertSessionHasNoErrors();
    expect($s->fresh()->status)->toBe('ready_funding');
    expect($p->fresh()->status)->toBe('approved');
});

it('creates an individual task per eligible member and supports revision and validation', function () {
    Storage::fake('local');
    $coordinator = departmentUser();
    $one = departmentUser('anggota_departemen');
    $two = departmentUser('anggota_departemen');
    $p = OpsProgram::factory()->create(['status' => 'running', 'ends_on' => now()->addDays(10)]);
    $p->members()->sync([$one->id, $two->id]);
    $this->actingAs($coordinator)->post(route('department.task.store', 'kaderisasi'), ['program_id' => $p->id, 'members' => [$one->id, $two->id], 'title' => 'Siapkan materi', 'description' => 'Susun materi.', 'deadline' => now()->addDays(3)->toDateString()])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('ops_tasks', 2);
    $task = OpsTask::where('assigned_to', $one->id)->firstOrFail();
    $this->actingAs($two)->post(route('department.task.submit', ['kaderisasi', $task->id]), ['version' => 0, 'action' => 'submit', 'result' => 'Bukan tugas saya'])->assertNotFound();
    $this->actingAs($one)->post(route('department.task.submit', ['kaderisasi', $task->id]), ['version' => 0, 'action' => 'submit', 'result' => 'Materi tahap pertama', 'file' => UploadedFile::fake()->create('hasil.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    Storage::disk('local')->assertExists($task->fresh()->file_path);
    $this->actingAs($coordinator)->get(route('department.monitoring', ['kaderisasi', 'task' => $task->id]))->assertOk()->assertSee('Materi tahap pertama');
    $this->post(route('department.task.review', ['kaderisasi', $task->id]), ['version' => 1, 'decision' => 'revision', 'feedback' => 'Tambahkan contoh'])->assertSessionHasNoErrors();
    $this->actingAs($one)->post(route('department.task.submit', ['kaderisasi', $task->id]), ['version' => 2, 'action' => 'submit', 'result' => 'Contoh ditambahkan'])->assertSessionHasNoErrors();
    $this->actingAs($coordinator)->post(route('department.task.review', ['kaderisasi', $task->id]), ['version' => 3, 'decision' => 'done', 'feedback' => 'Sudah sesuai'])->assertSessionHasNoErrors();
    expect($task->fresh()->status)->toBe('done');
    expect(OpsTask::where('assigned_to', $two->id)->first()->status)->toBe('assigned');
    $this->get(route('department.program.show', ['kaderisasi', $p->id]))->assertSee('50%');
});

it('rejects task assignment to members outside the program or department', function () {
    $p = OpsProgram::factory()->create(['status' => 'approved']);
    $other = departmentUser('anggota_departemen', 'humas');
    $this->actingAs(departmentUser())->post(route('department.task.store', 'kaderisasi'), ['program_id' => $p->id, 'members' => [$other->id], 'title' => 'Tugas', 'description' => 'Deskripsi', 'deadline' => now()->toDateString()])->assertSessionHasErrors('workflow');
    $this->assertDatabaseCount('ops_tasks', 0);
});

it('requires fully completed tasks and both LPJ approvals before secretary closes a program', function () {
    Storage::fake('local');
    $user = departmentUser();
    $p = OpsProgram::factory()->create(['status' => 'running', 'ends_on' => now()->subDay()]);
    $task = OpsTask::factory()->create(['program_id' => $p->id, 'status' => 'done']);
    $this->actingAs($user)->post(route('department.program.transition', ['kaderisasi', $p->id]), ['version' => 0, 'action' => 'finish'])->assertSessionHasErrors('workflow');
    $this->post(route('department.submission.save', ['kaderisasi', $p->id, 'report']), ['version' => 0, 'action' => 'submit', 'summary' => 'Kegiatan terlaksana.', 'file' => UploadedFile::fake()->create('lpj.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    $s = OpsSubmission::where('type', 'report')->firstOrFail();
    expect($s->status)->toBe('pending_chair');
    $this->actingAs(departmentUser('ketua_umum'))->post(route('department.review.save', $s->id), ['version' => 1, 'action' => 'approve', 'note' => 'Laporan diterima'])->assertSessionHasNoErrors();
    $this->actingAs(departmentUser('koordinator_pengawas'))->post(route('department.review.save', $s->id), ['version' => 2, 'action' => 'approve', 'note' => 'Telah diverifikasi'])->assertSessionHasNoErrors();
    $task->update(['status' => 'working']);
    $secretary = departmentUser('sekretaris_umum');
    $this->actingAs($secretary)->post(route('department.review.save', $s->id), ['version' => 3, 'action' => 'archive', 'note' => 'Arsip'])->assertSessionHasErrors('workflow');
    $task->update(['status' => 'done']);
    $this->post(route('department.review.save', $s->id), ['version' => 3, 'action' => 'archive', 'note' => 'Arsip'])->assertSessionHasNoErrors();
    expect($p->fresh()->status)->toBe('completed');
});

it('protects submission downloads across departments and reviewer roles', function () {
    Storage::fake('local');
    Storage::disk('local')->put('department/test.pdf', 'test');
    $s = OpsSubmission::factory()->create(['file_path' => 'department/test.pdf', 'status' => 'pending_secretary']);
    $this->actingAs(departmentUser('koordinator_departemen', 'humas'))->get(route('department.submission.file', $s->id))->assertNotFound();
    $this->actingAs(departmentUser('bendahara_umum'))->get(route('department.submission.file', $s->id))->assertNotFound();
    $this->actingAs(departmentUser())->get(route('department.submission.file', $s->id))->assertOk();
});

it('limits private forum groups and attachments to their members and escapes messages', function () {
    Storage::fake('local');
    $coordinator = departmentUser();
    $member = departmentUser('anggota_departemen');
    $other = departmentUser('anggota_departemen');
    $this->actingAs($coordinator)->post(route('department.forum.create', 'kaderisasi'), ['name' => 'Panitia Workshop', 'members' => [$member->id]])->assertSessionHasNoErrors();
    $g = OpsGroup::where('name', 'Panitia Workshop')->firstOrFail();
    $this->actingAs($other)->get(route('department.forum', ['kaderisasi', 'group' => $g->id]))->assertNotFound();
    $this->actingAs($member)->post(route('department.forum.send', ['kaderisasi', $g->id]), ['body' => '<script>alert(1)</script>', 'file' => UploadedFile::fake()->create('forum.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    $m = OpsMessage::firstOrFail();
    Storage::disk('local')->assertExists($m->file_path);
    $this->get(route('department.forum', ['kaderisasi', 'group' => $g->id]))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->actingAs($other)->get(route('department.forum.file', ['kaderisasi', $m->id]))->assertNotFound();
    $this->actingAs($coordinator)->get(route('department.forum.updates', ['kaderisasi', $g->id]))->assertJson(['latest_id' => $m->id]);
});

it('allows active HIMSI roles into the general forum without department management access', function () {
    $this->actingAs(departmentUser('panitia_cb'))->get(route('department.forum', 'umum'))->assertOk();
    $this->get(route('department.dashboard', 'umum'))->assertNotFound();
    $this->post(route('department.forum.create', 'umum'), ['name' => 'Tidak boleh', 'members' => [1]])->assertNotFound();
});

it('reads only finance rows explicitly linked to the selected department and program', function () {
    Schema::create('test_department_expenses', function (Blueprint $table) {
        $table->id();
        $table->string('department');
        $table->unsignedBigInteger('program_id')->nullable();
        $table->unsignedBigInteger('amount');
        $table->date('date');
        $table->string('description');
        $table->string('status');
    });
    config(['department.expenses' => ['table' => 'test_department_expenses', 'program' => 'program_id', 'department' => 'department', 'amount' => 'amount', 'date' => 'date', 'description' => 'description', 'status_column' => 'status', 'status_value' => 'posted']]);
    $p = OpsProgram::factory()->create();
    DB::table('test_department_expenses')->insert([
        ['department' => 'kaderisasi', 'program_id' => $p->id, 'amount' => 50000, 'date' => '2026-10-20', 'description' => 'Transaksi sesuai', 'status' => 'posted'],
        ['department' => 'humas', 'program_id' => $p->id, 'amount' => 99000, 'date' => '2026-10-20', 'description' => 'Transaksi departemen lain', 'status' => 'posted'],
        ['department' => 'kaderisasi', 'program_id' => null, 'amount' => 88000, 'date' => '2026-10-20', 'description' => 'Belum dikaitkan', 'status' => 'posted'],
        ['department' => 'kaderisasi', 'program_id' => $p->id, 'amount' => 77000, 'date' => '2026-10-20', 'description' => 'Masih draft', 'status' => 'draft'],
    ]);
    $this->actingAs(departmentUser())->get(route('department.submissions', ['kaderisasi', 'budget', 'tab' => 'expenses', 'program' => $p->id]))->assertOk()->assertSee('Rp 50.000')->assertSee('Transaksi sesuai')->assertDontSee('Transaksi departemen lain')->assertDontSee('Belum dikaitkan')->assertDontSee('Masih draft');
});

it('requires an approved budget before starting a program', function () {
    $p = OpsProgram::factory()->create(['status' => 'approved']);
    OpsSubmission::factory()->create(['program_id' => $p->id, 'type' => 'rpk', 'status' => 'archived']);
    $this->actingAs(departmentUser())->post(route('department.program.transition', ['kaderisasi', $p->id]), ['action' => 'start', 'version' => 0])->assertSessionHasErrors('workflow');
    OpsSubmission::factory()->create(['program_id' => $p->id, 'type' => 'budget', 'status' => 'disbursed']);
    $this->post(route('department.program.transition', ['kaderisasi', $p->id]), ['action' => 'start', 'version' => 0])->assertSessionHasNoErrors();
    expect($p->fresh()->status)->toBe('running');
});

it('does not expose unsubmitted drafts to reviewing roles', function () {
    $s = OpsSubmission::factory()->create();
    $this->actingAs(departmentUser('sekretaris_umum'))->get(route('department.review', $s->id))->assertNotFound();
});

it('links the six super admin department choices to the correct route parameters', function () {
    $this->actingAs(departmentUser('super_admin'))->get(route('admin.module', ['role' => 'koordinator_departemen', 'department' => 'humas']))->assertOk()->assertSee(route('department.dashboard', 'humas'));
});

it('allows LPJ drafts only after progress reaches one hundred percent', function () {
    Storage::fake('local');
    $this->freezeTime();
    $p = OpsProgram::factory()->create(['status' => 'running', 'ends_on' => now()->addDays(2)]);
    $this->actingAs(departmentUser())->post(route('department.submission.save', ['kaderisasi', $p->id, 'report']), ['version' => 0, 'action' => 'submit', 'summary' => 'Rangkuman', 'file' => UploadedFile::fake()->create('lpj.pdf', 10, 'application/pdf')])->assertSessionHasErrors('workflow');
    OpsTask::factory()->create(['program_id' => $p->id, 'status' => 'done']);
    $this->post(route('department.submission.save', ['kaderisasi', $p->id, 'report']), ['version' => 0, 'action' => 'draft', 'summary' => 'Rangkuman', 'file' => UploadedFile::fake()->create('lpj.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    expect(OpsSubmission::first()->status)->toBe('draft');
    Storage::disk('local')->assertExists(OpsSubmission::first()->file_path);
});

it('counts running programs still awaiting their LPJ on the dashboard', function () {
    OpsProgram::factory()->create(['status' => 'running']);
    $response = $this->actingAs(departmentUser())->get(route('department.dashboard', 'kaderisasi'))->assertOk();
    expect($response->viewData('stats')['LPJ Perlu Diselesaikan'])->toBe(1);
});

it('deletes an untouched draft and removes its member links', function () {
    $user = departmentUser();
    $program = OpsProgram::factory()->create(['pic_id' => $user->id]);
    $program->members()->attach($user);
    $this->actingAs($user)->get(route('department.programs', 'kaderisasi'))->assertSee('Hapus')->assertSee('Edit');
    $this->delete(route('department.program.destroy', ['kaderisasi', $program->id]), ['version' => 0])->assertRedirect(route('department.programs', 'kaderisasi'))->assertSessionHas('success');
    $this->assertDatabaseMissing('ops_programs', ['id' => $program->id]);
    $this->assertDatabaseMissing('ops_program_members', ['program_id' => $program->id]);
});

it('protects progressed programs from deletion', function (string $status) {
    $program = OpsProgram::factory()->create(['status' => $status]);
    $this->actingAs(departmentUser())->delete(route('department.program.destroy', ['kaderisasi', $program->id]), ['version' => 0])->assertSessionHasErrors('workflow');
    $this->assertDatabaseHas('ops_programs', ['id' => $program->id]);
})->with(['pending', 'approved', 'running', 'completed', 'revision', 'rejected']);

it('protects drafts containing submissions or tasks', function (string $relation) {
    $program = OpsProgram::factory()->create();
    if ($relation === 'submission') {
        OpsSubmission::factory()->create(['program_id' => $program->id]);
    } else {
        OpsTask::factory()->create(['program_id' => $program->id]);
    }
    $this->actingAs(departmentUser())->delete(route('department.program.destroy', ['kaderisasi', $program->id]), ['version' => 0])->assertSessionHasErrors('workflow');
    $this->assertDatabaseHas('ops_programs', ['id' => $program->id]);
})->with(['submission', 'task']);

it('rejects unauthorized stale and malformed deletion requests', function () {
    $program = OpsProgram::factory()->create(['version' => 2]);
    $url = route('department.program.destroy', ['kaderisasi', $program->id]);
    $this->delete($url, ['version' => 2])->assertRedirect(route('login'));
    $this->actingAs(departmentUser('anggota_departemen'))->delete($url, ['version' => 2])->assertForbidden();
    $this->actingAs(departmentUser('koordinator_departemen', 'humas'))->delete($url, ['version' => 2])->assertForbidden();
    $this->actingAs(departmentUser())->delete($url, ['version' => 1])->assertSessionHasErrors('version');
    $this->delete($url, [])->assertSessionHasErrors('version');
    $other = OpsProgram::factory()->create(['department' => 'humas']);
    $this->delete(route('department.program.destroy', ['kaderisasi', $other->id]), ['version' => 0])->assertNotFound();
    $this->assertDatabaseHas('ops_programs', ['id' => $program->id]);
});
