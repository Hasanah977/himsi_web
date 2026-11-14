<?php

use App\Models\FinanceEntry;
use App\Models\HimsiRegistration;
use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\PprkSubmission;
use App\Models\User;
use App\Support\FinanceLedger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('switches an appointed officer to KBH and back without changing the stored appointment', function (string $role) {
    $user = User::factory()->create(['role' => $role, 'department' => 'akademik', 'is_active' => true]);

    $this->actingAs($user)->post(route('workspace.update'), ['role' => 'kbh'])->assertRedirect(route('dashboard'));
    $this->get(route('kbh.dashboard'))->assertOk()->assertSee('Mode KBH');
    $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => $role, 'department' => 'akademik']);
    $this->post(route('workspace.update'), ['role' => $role])->assertRedirect(route('dashboard'));
    expect($user->fresh()->role)->toBe($role);
})->with(['ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen']);

it('does not grant officer access while KBH mode is active or accept an unassigned role', function () {
    $secretary = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $this->actingAs($secretary)->post(route('workspace.update'), ['role' => 'kbh']);

    $this->get(route('sekretaris.dashboard'))->assertForbidden();
    $this->post(route('workspace.update'), ['role' => 'super_admin'])->assertSessionHasErrors('role');
    $this->assertDatabaseHas('users', ['id' => $secretary->id, 'role' => 'sekretaris_umum']);
    $student = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($student)->post(route('workspace.update'), ['role' => 'sekretaris_umum'])->assertForbidden();
});

it('opens self registration for KBH and notifies students only once', function () {
    $student = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $manager = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);

    $this->actingAs($manager)->post(route('kpum.registration.toggle'), ['open' => 1])->assertSessionHasNoErrors();
    $this->post(route('kpum.registration.toggle'), ['open' => 1])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('kpum_elections', ['id' => 1, 'registration_open' => true]);
    expect(NotifikasiHimsi::where('user_id', $student->id)->count())->toBe(1);
    $this->get(route('kpum.candidates'))->assertSee('Tutup Pendaftaran Ketua Umum')->assertDontSee('Daftarkan Kandidat');
    $this->actingAs($student)->get(route('kbh.seleksi.index'))->assertSee('Daftar sebagai Ketua Umum');
    $this->get(route('kpum.registration.create'))->assertSee('Kirim Pendaftaran ke KPUM');
    $this->post(route('kpum.registration.toggle'), ['open' => 0])->assertForbidden();
});

function chairRegistrationPayload(): array
{
    $data = ['name' => 'Mahasiswa Uji', 'nim' => 'D0424001', 'angkatan' => 2024, 'ipk' => 3.4, 'vision' => 'Membangun HIMSI', 'mission' => 'Kolaborasi mahasiswa', 'photo' => UploadedFile::fake()->image('foto.jpg')];
    foreach (KpumCandidate::documentLabels() as $key => $label) {
        $data['documents'][$key] = UploadedFile::fake()->create($key.'.pdf', 10, 'application/pdf');
    }

    return $data;
}

it('stores private candidate files for the logged in KBH without accepting a ballot number or another owner', function () {
    Storage::fake('local');
    KpumElection::findOrFail(1)->update(['registration_open' => true]);
    $student = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    HimsiRegistration::factory()->create(['user_id' => $student->id, 'status' => 'approved', 'nim' => 'D0424001']);
    $manager = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);
    $data = chairRegistrationPayload() + ['number' => 9, 'status' => 'approved', 'user_id' => $manager->id];

    $this->actingAs($student)->post(route('kpum.registration.store'), $data)->assertRedirect(route('kbh.seleksi.index'))->assertSessionHasNoErrors();
    $candidate = KpumCandidate::firstOrFail();
    $this->assertDatabaseHas('kpum_candidates', ['id' => $candidate->id, 'user_id' => $student->id, 'number' => null, 'status' => 'pending']);
    Storage::disk('local')->assertExists(array_merge([$candidate->photo], array_values($candidate->documents)));
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $manager->id, 'judul' => 'Pendaftaran Ketua Umum baru']);
    $this->post(route('kpum.registration.store'), chairRegistrationPayload())->assertSessionHasErrors('registration');
    $this->assertDatabaseCount('kpum_candidates', 1);
    $this->get(route('kpum.candidates.document', [$candidate, 'ktm']))->assertForbidden();
});

it('rejects registration after closure and validates required documents', function () {
    Storage::fake('local');
    $student = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($student)->post(route('kpum.registration.store'), chairRegistrationPayload())->assertSessionHasErrors('registration');
    KpumElection::findOrFail(1)->update(['registration_open' => true]);
    $this->post(route('kpum.registration.store'), [])->assertSessionHasErrors(['photo', 'documents.ktm', 'documents.transcript', 'documents.propesif']);
    $this->assertDatabaseCount('kpum_candidates', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('assigns a unique ballot number on approval and notifies rejected applicants too', function () {
    Storage::fake('local');
    KpumElection::findOrFail(1)->update(['registration_open' => true]);
    $student = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($student)->post(route('kpum.registration.store'), chairRegistrationPayload())->assertSessionHasNoErrors();
    $candidate = KpumCandidate::firstOrFail();
    $manager = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);
    $review = ['status' => 'approved', 'note' => 'Semua berkas valid.', 'checked' => ['ktm', 'transcript', 'propesif']];

    $this->actingAs($manager)->post(route('kpum.candidates.review', $candidate), $review)->assertSessionHasErrors('number');
    $this->post(route('kpum.candidates.review', $candidate), $review + ['number' => 2])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('kpum_candidates', ['id' => $candidate->id, 'number' => 2, 'status' => 'approved']);
    expect(NotifikasiHimsi::where('user_id', $student->id)->latest('id')->first()->pesan)->toContain('nomor urut 2');
    $this->post(route('kpum.candidates.review', $candidate), ['status' => 'rejected', 'note' => 'Transkrip perlu diperbaiki.'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('kpum_candidates', ['id' => $candidate->id, 'number' => null, 'status' => 'rejected']);
    expect(NotifikasiHimsi::where('user_id', $student->id)->latest('id')->first()->pesan)->toContain('belum lolos');
});

it('accepts encrypted account identifiers and rejects raw or tampered identifiers', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $account = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $token = $account->encryptedAccountId();

    $this->actingAs($admin)->get(route('admin.users.edit', $token))->assertSee($account->name);
    $this->get(route('admin.users.edit', $account->id))->assertNotFound();
    $this->get(route('admin.users.edit', $token.'tampered'))->assertNotFound();
    $this->put(route('admin.users.update', $token), ['name' => 'Nama Diperbarui', 'email' => $account->email, 'role' => 'kbh', 'is_active' => 1])->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseHas('users', ['id' => $account->id, 'name' => 'Nama Diperbarui']);
    $this->actingAs($account)->get(route('admin.users.edit', $token))->assertForbidden();
});

it('filters notifications and keeps read delete and bulk read actions scoped to their owner', function () {
    $user = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);
    $other = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $unread = NotifikasiHimsi::create(['user_id' => $user->id, 'judul' => 'Berkas baru', 'pesan' => '<script>alert(1)</script>', 'url' => '/kpum/kandidat']);
    NotifikasiHimsi::create(['user_id' => $user->id, 'judul' => 'Sudah selesai', 'pesan' => 'Selesai', 'dibaca_pada' => now()]);
    $foreign = NotifikasiHimsi::create(['user_id' => $other->id, 'judul' => 'Milik orang lain', 'pesan' => 'Pribadi']);

    $this->actingAs($user)->get(route('administration.notifications', ['filter' => 'unread']))->assertSee('Berkas baru')->assertDontSee('Sudah selesai')->assertDontSee('Milik orang lain')->assertDontSee('<script>alert(1)</script>', false);
    $this->post(route('notifikasi-himsi.baca', $unread), ['mark_only' => 1])->assertRedirect();
    $this->assertDatabaseMissing('notifikasi_himsis', ['id' => $unread->id, 'dibaca_pada' => null]);
    $this->delete(route('notifikasi-himsi.destroy', $foreign))->assertForbidden();
    $this->post(route('notifikasi-himsi.read-all'))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('notifikasi_himsis', ['id' => $foreign->id, 'dibaca_pada' => null]);
    $this->get(route('administration.notifications', ['filter' => 'read']))->assertSee('Berkas baru')->assertSee('Sudah selesai');
});

it('records a coordinator return in the treasury and blocks duplicates excess and cross department access', function () {
    Storage::fake('local');
    $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));
    $coordinator = User::factory()->create(['role' => 'koordinator_departemen', 'department' => 'akademik', 'is_active' => true]);
    $treasurer = User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]);
    $program = OpsProgram::factory()->create(['department' => 'akademik', 'created_by' => $coordinator->id, 'pic_id' => $coordinator->id, 'status' => 'running']);
    $submission = OpsSubmission::factory()->create(['program_id' => $program->id, 'type' => 'budget', 'status' => 'disbursed', 'version' => 3, 'total' => 100000]);
    FinanceEntry::create(['reference' => 'opening-test', 'kind' => 'income', 'origin' => 'manual', 'occurred_on' => '2026-10-01', 'amount' => 200000, 'category' => 'Saldo Awal', 'description' => 'Kas awal', 'method' => 'Transfer']);
    FinanceEntry::create(['reference' => 'payment-test', 'kind' => 'expense', 'origin' => 'disbursement', 'occurred_on' => '2026-10-02', 'amount' => 100000, 'category' => 'Pencairan', 'description' => 'Dana proker', 'method' => 'Transfer', 'department' => 'akademik', 'program_id' => $program->id, 'submission_id' => $submission->id]);
    $data = ['version' => 3, 'reference' => (string) Str::uuid(), 'amount' => 25000, 'occurred_on' => '2026-10-29', 'receipt' => UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf')];

    $this->actingAs($coordinator)->get(route('department.submission.edit', ['akademik', $program, 'budget']))->assertSee('Dana yang dikembalikan');
    $this->post(route('department.budget.refund', ['akademik', $submission]), $data)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('finance_entries', ['origin' => 'return', 'amount' => 25000, 'submission_id' => $submission->id, 'actor_id' => $coordinator->id]);
    expect(FinanceLedger::balance())->toBe(125000);
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $treasurer->id, 'judul' => 'Pengembalian Dana Departemen']);
    $this->post(route('department.budget.refund', ['akademik', $submission]), $data)->assertSessionHasErrors('version');
    $data['version'] = 4;
    $data['reference'] = (string) Str::uuid();
    $data['amount'] = 75001;
    $this->post(route('department.budget.refund', ['akademik', $submission]), $data)->assertSessionHasErrors('finance');
    expect(FinanceEntry::where('origin', 'return')->count())->toBe(1);
    $other = User::factory()->create(['role' => 'koordinator_departemen', 'department' => 'humas', 'is_active' => true]);
    $this->actingAs($other)->post(route('department.budget.refund', ['humas', $submission]), $data)->assertNotFound();
    Storage::disk('local')->assertExists(FinanceEntry::where('origin', 'return')->firstOrFail()->receipt_path);
});

it('groups archived departmental reports and RPK under the secretary archive', function () {
    $secretary = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $report = OpsSubmission::factory()->create(['type' => 'report', 'status' => 'archived']);
    PprkSubmission::factory()->create(['department' => 'akademik', 'status' => 'archived', 'archived_at' => now()]);

    $this->actingAs($secretary)->get(route('sekretaris.arsip-surat.index', ['tab' => 'lpj']))->assertSee($report->program->title)->assertSee('LPJ Departemen');
    $this->get(route('sekretaris.arsip-surat.index', ['tab' => 'pprk']))->assertSee('Akademik')->assertSee('Unduh RPK');
});

it('keeps promoted KBH members on the treasury billing list', function () {
    $secretary = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    HimsiRegistration::factory()->create(['user_id' => $secretary->id, 'status' => 'approved']);
    $treasurer = User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]);

    $this->actingAs($treasurer)->get(route('finance.billing'))->assertViewHas('users', fn ($users) => $users->contains('id', $secretary->id));
});

it('opens KBH notification details from an officer workspace using the same account', function () {
    $secretary = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $notification = NotifikasiHimsi::create(['user_id' => $secretary->id, 'judul' => 'Iuran', 'pesan' => 'Tagihan tersedia', 'url' => route('kbh.iuran.index', [], false)]);

    $this->actingAs($secretary)->post(route('notifikasi-himsi.baca', $notification))->assertRedirect(route('kbh.iuran.index', [], false));
    $this->get(route('kbh.iuran.index'))->assertOk();
    $this->assertDatabaseHas('users', ['id' => $secretary->id, 'role' => 'sekretaris_umum']);
});

it('prevents an appointed officer from applying again through KBH mode', function () {
    $secretary = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $this->actingAs($secretary)->post(route('workspace.update'), ['role' => 'kbh']);

    $this->get(route('recruitment.apply'))->assertForbidden();
    $this->get(route('kpum.registration.create'))->assertForbidden();
});

it('rejects duplicate ballot numbers and accepts corrected rejected applications', function () {
    Storage::fake('local');
    KpumElection::findOrFail(1)->update(['registration_open' => true]);
    $first = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $second = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $manager = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);
    $this->actingAs($first)->post(route('kpum.registration.store'), chairRegistrationPayload())->assertSessionHasNoErrors();
    $data = chairRegistrationPayload();
    $data['nim'] = 'D0424002';
    $this->actingAs($second)->post(route('kpum.registration.store'), $data)->assertSessionHasNoErrors();
    $one = KpumCandidate::where('user_id', $first->id)->firstOrFail();
    $two = KpumCandidate::where('user_id', $second->id)->firstOrFail();
    $review = ['status' => 'approved', 'number' => 1, 'note' => 'Lengkap', 'checked' => ['ktm', 'transcript', 'propesif']];

    $this->actingAs($manager)->post(route('kpum.candidates.review', $one), $review)->assertSessionHasNoErrors();
    $this->post(route('kpum.candidates.review', $two), $review)->assertSessionHasErrors('number');
    $this->post(route('kpum.candidates.review', $two), ['status' => 'rejected', 'note' => 'Perbaiki berkas'])->assertSessionHasNoErrors();
    $oldPhoto = $two->photo;
    $newData = chairRegistrationPayload();
    $newData['nim'] = 'D0424002';
    $this->actingAs($second)->post(route('kpum.registration.store'), $newData)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('kpum_candidates', ['id' => $two->id, 'number' => null, 'status' => 'pending']);
    $this->assertDatabaseCount('kpum_candidates', 2);
    Storage::disk('local')->assertMissing($oldPhoto);
    Storage::disk('local')->assertExists($two->fresh()->photo);
});

it('blocks manual candidate creation by the committee', function () {
    $manager = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);

    $this->actingAs($manager)->post(route('kpum.candidates.store'), chairRegistrationPayload() + ['number' => 1])->assertForbidden();
    $this->assertDatabaseCount('kpum_candidates', 0);
});
