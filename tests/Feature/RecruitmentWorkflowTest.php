<?php

use App\Models\KbhPendaftaranSeleksi as Application;
use App\Models\KbhPeriode as Period;
use App\Models\NotifikasiHimsi;
use App\Models\User;
use App\Support\RecruitmentWorkflow as Flow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function recruitmentFiles(int $batch): array
{
    $files = [];
    foreach (array_keys(Flow::documents($batch)) as $key) {
        $files[$key] = UploadedFile::fake()->create($key.'.pdf', 10, 'application/pdf');
    }

    return $files;
}

it('requires authentication and reserves opening closing and appointment for the chair', function (string $role) {
    $this->get(route('recruitment.index'))->assertRedirect(route('login'));
    $user = User::factory()->create(['role' => $role, 'is_active' => true]);
    $period = Period::factory()->create();
    $this->actingAs($user)->post(route('recruitment.open'))->assertForbidden();
    $this->post(route('recruitment.close', $period))->assertForbidden();
    $this->post(route('recruitment.finalize', $period))->assertForbidden();
    expect($period->fresh()->stage)->toBe('registration');
})->with(['kbh', 'sekretaris_umum', 'bendahara_umum', 'super_admin', 'anggota_departemen']);

it('blocks batch two before batch one completes and rejects overlapping batches', function () {
    $this->freezeTime();
    $chair = User::factory()->create(['role' => 'ketua_umum', 'is_active' => true]);
    $data = ['batch' => 2, 'nama' => 'Periode uji', 'selesai_pada' => now()->addDays(2)->timezone('Asia/Makassar')->format('Y-m-d\TH:i')];
    $this->actingAs($chair)->post(route('recruitment.open'), $data)->assertSessionHasErrors(['recruitment' => 'Batch 1 harus selesai sebelum Batch 2 dibuka.']);
    $this->post(route('recruitment.open'), array_replace($data, ['batch' => 1]))->assertSessionHasNoErrors();
    $this->post(route('recruitment.open'), $data)->assertSessionHasErrors('recruitment');
    $this->assertDatabaseCount('kbh_periodes', 1);
    $this->get(route('recruitment.index'))->assertOk()->assertSee('Berkas & Wawancara Saya', false);
});

it('validates mandatory files and forbids duplicate registration and closed registration', function () {
    Storage::fake('local');
    $this->freezeTime();
    $period = Period::factory()->create();
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $data = ['period_id' => $period->id, 'position' => 'sekretaris_umum'];
    $this->actingAs($user)->post(route('recruitment.store'), $data)->assertSessionHasErrors(['documents.ktm', 'documents.transcript']);
    $this->post(route('recruitment.store'), $data + ['documents' => recruitmentFiles(1)])->assertSessionHasNoErrors()->assertRedirect(route('recruitment.result'));
    $application = Application::firstOrFail();
    Storage::disk('local')->assertExists(array_values($application->documents));
    expect($application->reviewer_id)->toBe($period->created_by);
    $this->post(route('recruitment.store'), $data + ['documents' => recruitmentFiles(1)])->assertSessionHasErrors('recruitment');
    $this->assertDatabaseCount('kbh_pendaftaran_seleksis', 1);
    expect(Storage::disk('local')->allFiles())->toHaveCount(2);
    $period->update(['aktif' => false, 'stage' => 'review']);
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))->post(route('recruitment.store'), $data + ['documents' => recruitmentFiles(1)])->assertSessionHasErrors('recruitment');
    $this->get(route('recruitment.result'))->assertOk()->assertDontSee($user->name);
});

it('isolates private files and review actions to the assigned reviewer', function () {
    Storage::fake('local');
    $application = Application::factory()->create(['documents' => ['ktm' => 'seleksi/ktm.pdf']]);
    Storage::disk('local')->put('seleksi/ktm.pdf', 'private');
    $other = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $this->actingAs($other)->get(route('recruitment.file', [$application, 'ktm']))->assertForbidden();
    $this->post(route('recruitment.review', $application), ['status' => 'lulus_berkas', 'catatan' => 'Lengkap'])->assertForbidden();
    $this->post(route('administration.selection.review', $application), ['status' => 'lolos'])->assertForbidden();
    $this->get(route('administration.selection.file', [$application, 'khs']))->assertForbidden();
    $this->actingAs($application->reviewer)->get(route('recruitment.file', [$application, 'ktm']))->assertDownload('ktm.pdf');
    expect($application->fresh()->status)->toBe('menunggu');
    Storage::disk('local')->assertExists('seleksi/ktm.pdf');
});

it('requires closed registration and all file decisions before scheduling and sends WITA notices', function () {
    $this->travelTo(now()->setDate(2026, 10, 26)->setTime(0, 0));
    $period = Period::factory()->create();
    $one = Application::factory()->create(['periode_id' => $period->id]);
    $two = Application::factory()->create(['periode_id' => $period->id]);
    $data = ['interview_at' => '2026-10-27T09:30', 'interview_location' => 'Sekretariat HIMSI'];
    $this->actingAs($one->reviewer)->post(route('recruitment.review', $one), ['status' => 'lulus_berkas', 'catatan' => 'Berkas lengkap'])->assertSessionHasNoErrors();
    $this->post(route('recruitment.schedule', $one), $data)->assertSessionHasErrors('recruitment');
    $this->post(route('recruitment.close', $period))->assertSessionHasNoErrors();
    $this->post(route('recruitment.schedule', $one), $data)->assertSessionHasErrors('recruitment');
    $this->post(route('recruitment.review', $two), ['status' => 'tidak_lolos', 'catatan' => 'Berkas tidak memenuhi syarat'])->assertSessionHasNoErrors();
    $this->post(route('recruitment.schedule', $one), $data)->assertSessionHasNoErrors();
    expect($one->fresh()->interview_at->format('H:i'))->toBe('01:30');
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $one->user_id, 'pesan' => 'Anda lulus berkas. Wawancara: 27 Oct 2026 09:30 WITA di Sekretariat HIMSI.']);
    $this->actingAs($one->user)->get(route('kbh.seleksi.hasil'))->assertOk()->assertSee('09:30')->assertSee('WITA')->assertSee('Sekretariat HIMSI');
    $this->actingAs($two->user)->get(route('recruitment.result'))->assertOk()->assertSee('Belum Lulus Seleksi')->assertSee('Berkas tidak memenuhi syarat');
});

it('completes both batches with disjoint reviewers shared ranking and automatic department coordinators', function () {
    Storage::fake('local');
    $this->freezeTime();
    $chair = User::factory()->create(['role' => 'ketua_umum', 'is_active' => true]);
    $period = Period::factory()->create(['created_by' => $chair->id]);
    $officers = [];
    foreach (['sekretaris_umum', 'bendahara_umum'] as $role) {
        $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
        $this->actingAs($user)->get(route('recruitment.apply'))->assertOk()->assertSee('KTM');
        $this->post(route('recruitment.store'), ['period_id' => $period->id, 'position' => $role, 'documents' => recruitmentFiles(1)])->assertSessionHasNoErrors();
        $application = Application::where('user_id', $user->id)->firstOrFail();
        $officers[$role] = $application;
        $this->actingAs($chair)->post(route('recruitment.review', $application), ['status' => 'lulus_berkas', 'catatan' => 'Lengkap'])->assertSessionHasNoErrors();
    }
    $this->actingAs($chair)->post(route('recruitment.close', $period))->assertSessionHasNoErrors();
    foreach ($officers as $application) {
        $this->post(route('recruitment.schedule', $application), ['interview_at' => now()->addHour()->timezone('Asia/Makassar')->format('Y-m-d\TH:i'), 'interview_location' => 'Ruang A'])->assertSessionHasNoErrors();
    }
    $this->travel(2)->hours();
    foreach ($officers as $application) {
        $this->get(route('recruitment.interview', $application))->assertOk()->assertSee('Simpan Nilai');
        $this->post(route('recruitment.score', $application), ['scores' => array_fill_keys(array_keys(Flow::questions()), 80), 'interview_notes' => 'Kompeten', 'outcome' => 'pass'])->assertSessionHasNoErrors();
    }
    $this->post(route('recruitment.finalize', $period), ['confirmed' => 1, 'sekretaris_umum' => $officers['sekretaris_umum']->id, 'bendahara_umum' => $officers['bendahara_umum']->id])->assertSessionHasNoErrors();
    foreach ($officers as $role => $application) {
        $this->assertDatabaseHas('users', ['id' => $application->user_id, 'role' => $role]);
        $this->assertDatabaseHas('member_appointments', ['user_id' => $application->user_id, 'role' => $role, 'source_key' => 'selection:'.$application->id, 'ended_at' => null]);
    }
    $this->post(route('recruitment.open'), ['batch' => 2, 'nama' => 'Departemen HIMSI', 'selesai_pada' => now()->addDays(2)->timezone('Asia/Makassar')->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();
    $batchTwo = Period::latest('id')->firstOrFail();
    $members = [];
    foreach ([70, 90, 80] as $score) {
        $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
        $this->actingAs($user)->post(route('recruitment.store'), ['period_id' => $batchTwo->id, 'documents' => recruitmentFiles(2), 'department_choices' => ['akademik', 'humas'], 'department_reasons' => ['Ingin berbagi ilmu akademik', 'Membangun komunikasi publik']])->assertSessionHasNoErrors();
        $application = Application::where('user_id', $user->id)->firstOrFail();
        $members[$score] = $application;
        $this->actingAs($application->reviewer)->post(route('recruitment.review', $application), ['status' => 'lulus_berkas', 'catatan' => 'Sesuai'])->assertSessionHasNoErrors();
    }
    expect(Application::where('periode_id', $batchTwo->id)->distinct()->count('reviewer_id'))->toBe(3);
    $this->actingAs($chair)->post(route('recruitment.close', $batchTwo))->assertSessionHasNoErrors();
    foreach ($members as $application) {
        $this->actingAs($application->reviewer)->post(route('recruitment.schedule', $application), ['interview_at' => now()->addHour()->timezone('Asia/Makassar')->format('Y-m-d\TH:i'), 'interview_location' => 'Ruang B'])->assertSessionHasNoErrors();
    }
    $this->travel(2)->hours();
    foreach ($members as $score => $application) {
        $this->actingAs($application->reviewer)->post(route('recruitment.score', $application), ['scores' => array_fill_keys(array_keys(Flow::questions()), $score), 'interview_notes' => 'Cocok untuk Akademik', 'outcome' => 'pass', 'assigned_department' => 'akademik'])->assertSessionHasNoErrors();
    }
    $this->actingAs($chair)->get(route('recruitment.index'))->assertOk()->assertSeeInOrder([$members[90]->user->name, $members[80]->user->name], false);
    $this->post(route('recruitment.finalize', $batchTwo), ['confirmed' => 1])->assertSessionHasNoErrors();
    $this->get(route('recruitment.index'))->assertOk()->assertSee('90.00')->assertSee('70.00');
    $this->assertDatabaseHas('users', ['id' => $members[90]->user_id, 'role' => 'koordinator_departemen', 'department' => 'akademik']);
    $this->assertDatabaseHas('users', ['id' => $members[70]->user_id, 'role' => 'anggota_departemen', 'department' => 'akademik']);
    $this->actingAs($members[90]->user->fresh())->get(route('recruitment.result'))->assertOk()->assertSee('Selamat,')->assertSee('Koordinator Departemen');
    expect(NotifikasiHimsi::where('user_id', $members[90]->user_id)->count())->toBe(3);
    Storage::disk('local')->assertExists(array_values($members[90]->documents));
    $this->actingAs($chair)->post(route('recruitment.finalize', $batchTwo), ['confirmed' => 1])->assertSessionHasErrors('recruitment');
});

it('rejects identical department choices missing files and invalid scores or placements', function () {
    Storage::fake('local');
    $this->freezeTime();
    $period = Period::factory()->create(['batch' => 2]);
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($user)->post(route('recruitment.store'), ['period_id' => $period->id, 'department_choices' => ['humas', 'humas'], 'department_reasons' => ['Alasan pertama cukup', 'Alasan kedua cukup']])->assertSessionHasErrors(['department_choices.0', 'documents.propesif', 'documents.ktm', 'documents.khs', 'documents.transcript', 'documents.supporting']);
    $period->update(['stage' => 'review', 'aktif' => false]);
    $application = Application::factory()->create(['periode_id' => $period->id, 'status' => 'wawancara', 'interview_at' => now()->subHour(), 'department_choices' => ['humas', 'akademik']]);
    $payload = ['scores' => array_fill_keys(array_keys(Flow::questions()), 80), 'interview_notes' => 'Catatan', 'outcome' => 'pass', 'assigned_department' => 'advokasi'];
    $this->actingAs($application->reviewer)->post(route('recruitment.score', $application), $payload)->assertSessionHasErrors('recruitment');
    $payload['assigned_department'] = 'akademik';
    $payload['scores']['commitment'] = 101;
    $this->post(route('recruitment.score', $application), $payload)->assertSessionHasErrors('scores.commitment');
    expect($application->fresh()->score)->toBeNull();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('requires a chair tie decision among the highest scoring department candidates', function () {
    $this->freezeTime();
    $period = Period::factory()->create(['batch' => 2, 'stage' => 'review', 'aktif' => false]);
    $members = Application::factory()->count(2)->create(['periode_id' => $period->id, 'status' => 'dinilai', 'score' => 85, 'assigned_department' => 'humas']);
    $chair = User::findOrFail($period->created_by);
    $this->actingAs($chair)->post(route('recruitment.finalize', $period), ['confirmed' => 1])->assertSessionHasErrors('recruitment');
    $this->assertDatabaseCount('notifikasi_himsis', 0);
    $this->post(route('recruitment.finalize', $period), ['confirmed' => 1, 'coordinators' => ['humas' => $members[1]->id]])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', ['id' => $members[1]->user_id, 'role' => 'koordinator_departemen']);
    $this->assertDatabaseHas('users', ['id' => $members[0]->user_id, 'role' => 'anggota_departemen']);
});

it('reopens a closed batch before interviews but preserves the interview stage once scheduled', function () {
    $this->freezeTime();
    $period = Period::factory()->create(['stage' => 'review', 'aktif' => false]);
    $chair = User::findOrFail($period->created_by);
    $end = now()->addDay()->timezone('Asia/Makassar')->format('Y-m-d\TH:i');
    $this->actingAs($chair)->post(route('recruitment.reopen', $period), ['selesai_pada' => $end])->assertSessionHasNoErrors();
    expect($period->fresh()->sedangDibuka())->toBeTrue();
    $this->post(route('recruitment.close', $period))->assertSessionHasNoErrors();
    Application::factory()->create(['periode_id' => $period->id, 'status' => 'wawancara', 'interview_at' => now()->addHour()]);
    $this->post(route('recruitment.reopen', $period), ['selesai_pada' => $end])->assertSessionHasErrors('recruitment');
    expect($period->fresh()->stage)->toBe('review');
});

it('preserves fractional interview scores so different results are not treated as a tie', function () {
    $this->freezeTime();
    $period = Period::factory()->create(['stage' => 'review', 'aktif' => false]);
    $application = Application::factory()->create(['periode_id' => $period->id, 'status' => 'wawancara', 'interview_at' => now()->subHour()]);
    $this->actingAs($application->reviewer)->post(route('recruitment.score', $application), [
        'scores' => ['commitment' => 81, 'integrity' => 82, 'teamwork' => 83, 'competence' => 84, 'initiative' => 86],
        'interview_notes' => 'Penilaian lengkap', 'outcome' => 'pass',
    ])->assertSessionHasNoErrors();
    expect($application->fresh()->score)->toBe('83.20');
});

it('lets the chair select the interviewed officers and notifies the remaining applicant of the final result', function () {
    $this->freezeTime();
    $period = Period::factory()->create(['stage' => 'review', 'aktif' => false]);
    $secretary = Application::factory()->create(['periode_id' => $period->id, 'status' => 'dinilai', 'score' => 80]);
    $treasurer = Application::factory()->create(['periode_id' => $period->id, 'position' => 'bendahara_umum', 'status' => 'dinilai', 'score' => 85]);
    $other = Application::factory()->create(['periode_id' => $period->id, 'status' => 'dinilai', 'score' => 90, 'catatan' => 'Berkas lengkap']);
    $this->actingAs(User::findOrFail($period->created_by))->post(route('recruitment.finalize', $period), ['confirmed' => 1, 'sekretaris_umum' => $secretary->id, 'bendahara_umum' => $treasurer->id])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', ['id' => $secretary->user_id, 'role' => 'sekretaris_umum']);
    $this->assertDatabaseHas('users', ['id' => $other->user_id, 'role' => 'kbh']);
    $this->assertDatabaseHas('kbh_pendaftaran_seleksis', ['id' => $other->id, 'status' => 'tidak_lolos']);
    $this->actingAs($other->user)->get(route('recruitment.result'))->assertOk()->assertSee('Anda belum terpilih pada seleksi akhir periode ini.')->assertDontSee('Berkas lengkap');
    $this->actingAs($secretary->user->fresh())->get(route('kbh.seleksi.hasil'))->assertRedirect(route('recruitment.result'));
});
