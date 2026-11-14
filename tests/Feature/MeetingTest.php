<?php

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\NotifikasiHimsi;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function meetingOfficer(string $role = 'sekretaris_umum'): User
{
    return User::factory()->create(['role' => $role, 'is_active' => true, 'department' => 'kaderisasi']);
}

it('sends invitations only to active roles in the selected category', function (string $category, array $roles): void {
    $this->freezeTime();
    $secretary = meetingOfficer();
    $users = collect(['ketua_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen', 'kbh', 'panitia_kpum', 'panitia_cb', 'super_admin', 'koordinator_pengawas'])->mapWithKeys(fn ($role) => [$role => meetingOfficer($role)]);
    $inactive = User::factory()->create(['role' => 'ketua_umum', 'is_active' => false]);

    $this->actingAs($secretary)->post(route('meetings.store'), ['title' => 'Evaluasi HIMSI', 'category' => $category, 'scheduled_at' => now()->addDay()->timezone('Asia/Makassar')->format('Y-m-d\TH:i'), 'location' => 'Ruang Rapat'])->assertSessionHasNoErrors();

    $meeting = Meeting::sole();
    expect($meeting->attendances()->pluck('participant_role')->sort()->values()->all())->toBe(collect($roles)->sort()->values()->all());
    expect(NotifikasiHimsi::where('kategori', 'rapat')->count())->toBe(count($roles));
    $this->assertDatabaseMissing('meeting_attendances', ['user_id' => $inactive->id]);
    $this->get(route('meetings.show', $meeting))->assertSee('Mulai Absen');
})->with([
    'monthly' => ['monthly', ['sekretaris_umum', 'ketua_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen']],
    'quarterly' => ['quarterly', ['sekretaris_umum', 'ketua_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen']],
    'board' => ['board', ['sekretaris_umum', 'ketua_umum', 'bendahara_umum', 'koordinator_departemen']],
    'presidium' => ['presidium', ['sekretaris_umum', 'ketua_umum', 'bendahara_umum']],
]);

it('denies excluded roles access to meetings', function (string $role): void {
    $this->actingAs(meetingOfficer($role))->get(route('meetings.index'))->assertForbidden();
    $this->post(route('meetings.store'), [])->assertForbidden();
})->with(['kbh', 'panitia_kpum', 'panitia_cb', 'super_admin', 'koordinator_pengawas']);

it('requires authentication and validates invitation fields without side effects', function (): void {
    $this->get(route('meetings.index'))->assertRedirect(route('login'));
    $this->actingAs(meetingOfficer())->post(route('meetings.store'), [])->assertSessionHasErrors(['title', 'category', 'scheduled_at', 'location']);
    $this->post(route('meetings.store'), ['title' => 'Rapat', 'category' => 'invalid', 'scheduled_at' => 'invalid', 'location' => 'Kampus'])->assertSessionHasErrors(['category', 'scheduled_at']);
    $this->assertDatabaseCount('meetings', 0);
    $this->assertDatabaseCount('notifikasi_himsis', 0);
});

it('shows meeting navigation and prevents participants from managing meetings', function (string $role): void {
    $user = meetingOfficer($role);
    $meeting = Meeting::factory()->create();
    $this->actingAs($user)->get(route('meetings.index'))->assertSee('Rapat Himpunan')->assertDontSee('Buat Undangan Rapat');
    $this->post(route('meetings.store'), [])->assertForbidden();
    $this->post(route('meetings.open', $meeting))->assertForbidden();
    $this->get(route('meetings.live', $meeting))->assertForbidden();
    $this->get(route('meetings.export'))->assertForbidden();
    $this->post(route('meetings.close', $meeting))->assertForbidden();
    $this->post(route('meetings.minutes', $meeting), ['minutes' => 'Palsu'])->assertForbidden();
})->with(['ketua_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen']);

it('prevents uninvited users from reading minutes or checking in', function (): void {
    $meeting = Meeting::factory()->create(['category' => 'presidium', 'title' => 'Rapat privat', 'minutes' => 'Catatan privat']);
    $this->actingAs(meetingOfficer('anggota_departemen'))->get(route('meetings.index'))->assertDontSee('Rapat privat');
    $this->get(route('meetings.show', $meeting))->assertNotFound();
    $this->post(route('meetings.attend', $meeting), ['code' => 'ABCDEF1234', 'source' => 'code'])->assertNotFound();
});

it('rotates codes at exactly two minutes and records attendance idempotently', function (): void {
    $this->freezeTime();
    $secretary = meetingOfficer();
    $member = meetingOfficer('anggota_departemen');
    $meeting = Meeting::factory()->create(['created_by' => $secretary, 'scheduled_at' => now()->subMinute()]);
    $attendance = MeetingAttendance::factory()->create(['meeting_id' => $meeting, 'user_id' => $member]);
    $this->actingAs($secretary)->post(route('meetings.open', $meeting))->assertSessionHasNoErrors();
    $first = $this->getJson(route('meetings.live', $meeting))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('token.code');
    $this->travel(119)->seconds();
    $this->getJson(route('meetings.live', $meeting))->assertJsonPath('token.code', $first)->assertJsonPath('token.remaining_seconds', 1);
    $this->travel(1)->seconds();
    $second = $this->getJson(route('meetings.live', $meeting))->json('token.code');
    expect($second)->not->toBe($first);
    $this->actingAs($member)->post(route('meetings.attend', $meeting), ['code' => $first, 'source' => 'code'])->assertSessionHasErrors('workflow');
    expect($attendance->refresh()->status)->toBe('pending');
    $this->post(route('meetings.attend', $meeting), ['code' => $second, 'source' => 'qr'])->assertRedirect(route('meetings.show', $meeting))->assertSessionHasNoErrors();
    $attendedAt = $attendance->refresh()->attended_at->toDateTimeString();
    $this->travel(5)->seconds();
    $this->post(route('meetings.attend', $meeting), ['code' => $second, 'source' => 'code'])->assertSessionHasNoErrors();
    expect($attendance->refresh())->status->toBe('present')->source->toBe('qr')->version->toBe(1);
    expect($attendance->attended_at->toDateTimeString())->toBe($attendedAt);
    $this->assertDatabaseCount('meeting_attendances', 1);
    $this->actingAs($secretary)->getJson(route('meetings.live', $meeting))->assertJsonPath('attendances.0.status', 'present');
});

it('records manual statuses closes attendance and publishes escaped minutes', function (): void {
    $this->freezeTime();
    $secretary = meetingOfficer();
    $member = meetingOfficer('anggota_departemen');
    $meeting = Meeting::factory()->create(['created_by' => $secretary, 'status' => 'open', 'attendance_opened_at' => now()]);
    $attendance = MeetingAttendance::factory()->create(['meeting_id' => $meeting, 'user_id' => $member]);
    $absent = MeetingAttendance::factory()->create(['meeting_id' => $meeting]);
    $this->actingAs($secretary)->post(route('meetings.minutes', $meeting), ['minutes' => 'Terlalu awal'])->assertSessionHasErrors('workflow');
    $this->post(route('meetings.manual', [$meeting, $attendance]), ['version' => 0, 'status' => 'excused'])->assertSessionHasNoErrors();
    $this->post(route('meetings.manual', [$meeting, $attendance]), ['version' => 0, 'status' => 'present'])->assertSessionHasErrors('version');
    $code = $this->getJson(route('meetings.live', $meeting))->json('token.code');
    $this->actingAs($member)->post(route('meetings.attend', $meeting), ['code' => $code, 'source' => 'code'])->assertSessionHasErrors('workflow');
    $this->actingAs($secretary)->post(route('meetings.close', $meeting))->assertSessionHasNoErrors();
    expect($attendance->refresh()->status)->toBe('excused');
    expect($absent->fresh()->status)->toBe('absent');
    $this->post(route('meetings.open', $meeting))->assertSessionHasErrors('workflow');
    $this->post(route('meetings.manual', [$meeting, $attendance]), ['version' => 1, 'status' => 'present'])->assertSessionHasErrors('workflow');
    $this->actingAs($member)->post(route('meetings.attend', $meeting), ['code' => $code, 'source' => 'code'])->assertSessionHasErrors('workflow');
    $this->actingAs($secretary)->post(route('meetings.minutes', $meeting), ['minutes' => '<script>alert(1)</script> Keputusan rapat.'])->assertSessionHasNoErrors();
    expect($meeting->fresh()->status)->toBe('completed');
    $this->actingAs($member)->get(route('meetings.show', $meeting))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertSee('Keputusan rapat.');
});

it('rejects attendance belonging to another meeting and all invalid manual statuses', function (): void {
    $secretary = meetingOfficer();
    $meeting = Meeting::factory()->create(['status' => 'open', 'attendance_opened_at' => now()]);
    $other = MeetingAttendance::factory()->create();
    $this->actingAs($secretary)->post(route('meetings.manual', [$meeting, $other]), ['version' => 0, 'status' => 'present'])->assertNotFound();
    $this->post(route('meetings.manual', [$meeting, $other]), ['version' => 0, 'status' => 'pending'])->assertSessionHasErrors('status');
    expect($other->fresh()->status)->toBe('pending');
});

it('filters history and exports a valid PDF for one category', function (): void {
    $secretary = meetingOfficer();
    Meeting::factory()->has(MeetingAttendance::factory()->count(35), 'attendances')->create(['category' => 'monthly', 'title' => 'Rapat Bulanan Terpilih', 'minutes' => str_repeat('Hasil pembahasan dan tindak lanjut rapat. ', 300)]);
    Meeting::factory()->create(['category' => 'presidium', 'title' => 'Rapat Lainnya']);
    $this->actingAs($secretary)->get(route('meetings.index', ['category' => 'monthly']))->assertSee('Rapat Bulanan Terpilih')->assertDontSee('Rapat Lainnya');
    $response = $this->get(route('meetings.export', ['category' => 'monthly']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-')->toContain('%%EOF');
    $this->get(route('meetings.export', ['category' => 'invalid']))->assertSessionHasErrors('category');
});

it('stores WITA schedules in UTC and lets the secretary open attendance', function (): void {
    $this->travelTo(Carbon::parse('2026-11-01 00:00:00', 'UTC'));
    $secretary = meetingOfficer();
    $this->actingAs($secretary)->post(route('meetings.store'), ['title' => 'Rapat Pagi', 'category' => 'monthly', 'scheduled_at' => '2026-11-01T09:00', 'location' => 'Kampus'])->assertSessionHasNoErrors();
    $meeting = Meeting::sole();
    expect($meeting->scheduled_at->format('Y-m-d H:i:s'))->toBe('2026-11-01 01:00:00');
    $this->get(route('meetings.show', $meeting))->assertSee('01/11/2026 09:00');
    $this->post(route('meetings.open', $meeting))->assertSessionHasNoErrors();
    $this->post(route('meetings.store'), ['title' => 'Jadwal Lama', 'category' => 'monthly', 'scheduled_at' => '2026-11-01T07:00', 'location' => 'Kampus'])->assertSessionHasErrors('workflow');
    $this->assertDatabaseCount('meetings', 1);
});

it('accepts each supported manual status and records the responsible secretary', function (string $status): void {
    $secretary = meetingOfficer();
    $attendance = MeetingAttendance::factory()->for(Meeting::factory()->state(['status' => 'open', 'attendance_opened_at' => now()]))->create();
    $this->actingAs($secretary)->post(route('meetings.manual', [$attendance->meeting, $attendance]), ['version' => 0, 'status' => $status])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('meeting_attendances', ['id' => $attendance->id, 'status' => $status, 'source' => 'manual', 'recorded_by' => $secretary->id, 'version' => 1]);
    expect($attendance->fresh()->attended_at !== null)->toBe($status === 'present');
})->with(['present', 'excused', 'absent']);
