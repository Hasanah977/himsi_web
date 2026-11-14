<?php

use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\PublicContent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function kpumManager(): User
{
    return User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);
}

function kpumCandidate(array $attributes = []): KpumCandidate
{
    return KpumCandidate::create(array_merge([
        'election_id' => 1, 'number' => 1, 'name' => 'Kandidat Uji', 'nim' => 'D0712001',
        'angkatan' => 2024, 'ipk' => 3.5, 'vision' => 'Visi pengujian', 'mission' => 'Misi pengujian', 'status' => 'approved',
    ], $attributes));
}

function kpumOpenElection(): void
{
    KpumElection::findOrFail(1)->update(['status' => 'open', 'period' => '2026/2027', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
}

it('requires authentication and a permitted role for KPUM management', function () {
    $this->get(route('kpum.dashboard'))->assertRedirect(route('login'));
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($user)->get(route('kpum.dashboard'))->assertForbidden();
    $this->actingAs($user)->post(route('kpum.open'))->assertForbidden();
    $this->assertDatabaseHas('kpum_elections', ['id' => 1, 'status' => 'draft']);
});

it('allows super admin to access the KPUM module', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($user)->get(route('kpum.dashboard'))->assertOk()->assertSee('Dashboard Panitia KPUM');
});

it('renders every management screen without sample votes', function (string $route) {
    $this->actingAs(kpumManager())->get(route($route))->assertOk();
    $this->assertDatabaseCount('kpum_ballots', 0);
})->with(['kpum.dashboard', 'kpum.candidates', 'kpum.candidates.create', 'kpum.control', 'kpum.voters', 'kpum.results', 'kpum.announcement']);

it('redirects the KPUM account from the shared dashboard', function () {
    $this->actingAs(kpumManager())->get(route('dashboard'))->assertRedirect(route('kpum.dashboard'));
});

it('rejects a deactivated committee account', function () {
    $user = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => false]);
    $this->actingAs($user)->get(route('kpum.dashboard'))->assertRedirect(route('login'));
});

it('stores candidate data and resets verification after an edit', function () {
    $candidate = kpumCandidate();
    $this->actingAs(kpumManager())->put(route('kpum.candidates.update', $candidate), [
        'name' => 'Nama Baru', 'nim' => $candidate->nim, 'number' => 1, 'angkatan' => 2024,
        'ipk' => 3.5, 'vision' => 'Visi baru', 'mission' => 'Misi baru',
    ])->assertRedirect(route('kpum.candidates'))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('kpum_candidates', ['id' => $candidate->id, 'status' => 'pending', 'name' => 'Nama Baru']);
});

it('refuses verification when candidate documents are missing', function () {
    $candidate = kpumCandidate(['status' => 'pending']);
    $this->actingAs(kpumManager())->post(route('kpum.candidates.review', $candidate), [
        'status' => 'approved', 'note' => 'Sudah diperiksa', 'checked' => array_keys(KpumCandidate::documentLabels()),
    ])->assertSessionHasErrors('review');
    $this->assertDatabaseHas('kpum_candidates', ['id' => $candidate->id, 'status' => 'pending']);
});

it('approves only complete documents and sufficient IPK', function () {
    Storage::fake('local');
    $documents = [];
    foreach (KpumCandidate::documentLabels() as $key => $label) {
        $documents[$key] = UploadedFile::fake()->create($key.'.pdf', 10, 'application/pdf')->store('kpum', 'local');
    }
    $candidate = kpumCandidate(['status' => 'pending', 'documents' => $documents]);
    $this->actingAs(kpumManager())->post(route('kpum.candidates.review', $candidate), [
        'status' => 'approved', 'note' => 'Berkas valid', 'checked' => array_keys($documents),
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('kpum_candidates', ['id' => $candidate->id, 'status' => 'approved']);
    Storage::disk('local')->assertExists(array_values($documents));
});

it('prevents opening without verified candidates and eligible voters', function () {
    $this->freezeTime();
    KpumElection::findOrFail(1)->update(['starts_at' => now(), 'ends_at' => now()->addHour()]);
    $this->actingAs(kpumManager())->post(route('kpum.open'))->assertSessionHasErrors('open');
    $this->assertDatabaseHas('kpum_elections', ['id' => 1, 'status' => 'draft']);
});

it('locks candidate edits and voter changes once voting is enabled', function () {
    $this->freezeTime();
    kpumOpenElection();
    $candidate = kpumCandidate();
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs(kpumManager())->delete(route('kpum.candidates.destroy', $candidate))->assertSessionHasErrors('kpum');
    $this->post(route('kpum.voters.update', $user), ['include' => 1])->assertSessionHasErrors('kpum');
    $this->assertDatabaseHas('kpum_candidates', ['id' => $candidate->id]);
    $this->assertDatabaseCount('kpum_electors', 0);
});

it('stores one ballot and refuses a second submission by the same voter', function () {
    $this->freezeTime();
    kpumOpenElection();
    $candidate = kpumCandidate();
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    DB::table('kpum_electors')->insert(['election_id' => 1, 'user_id' => $user->id]);
    $this->actingAs($user)->post(route('kpum.vote.submit'), ['candidate_id' => $candidate->id, 'confirmed' => 1])->assertRedirect(route('kpum.vote'))->assertSessionHasNoErrors();
    $this->post(route('kpum.vote.submit'), ['candidate_id' => $candidate->id, 'confirmed' => 1])->assertSessionHasErrors('vote');
    $this->assertDatabaseCount('kpum_ballots', 1);
    $this->assertDatabaseHas('kpum_electors', ['user_id' => $user->id, 'has_voted' => true]);
});

it('rejects unregistered voters and unverified candidates', function () {
    $this->freezeTime();
    kpumOpenElection();
    $candidate = kpumCandidate(['status' => 'pending']);
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($user)->post(route('kpum.vote.submit'), ['candidate_id' => $candidate->id, 'confirmed' => 1])->assertForbidden();
    DB::table('kpum_electors')->insert(['election_id' => 1, 'user_id' => $user->id]);
    $this->post(route('kpum.vote.submit'), ['candidate_id' => $candidate->id, 'confirmed' => 1])->assertSessionHasErrors('candidate_id');
    $this->assertDatabaseCount('kpum_ballots', 0);
});

it('rejects votes at the end of the voting period', function () {
    $this->freezeTime();
    kpumOpenElection();
    KpumElection::findOrFail(1)->update(['ends_at' => now()]);
    $candidate = kpumCandidate();
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    DB::table('kpum_electors')->insert(['election_id' => 1, 'user_id' => $user->id]);
    $this->actingAs($user)->post(route('kpum.vote.submit'), ['candidate_id' => $candidate->id, 'confirmed' => 1])->assertSessionHasErrors('vote');
    $this->assertDatabaseCount('kpum_ballots', 0);
});

it('does not let a voter download candidate private documents', function () {
    $candidate = kpumCandidate();
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($user)->get(route('kpum.candidates.document', [$candidate, 'ktm']))->assertForbidden();
});

it('refuses publication before certification', function () {
    $this->actingAs(kpumManager())->post(route('kpum.publish'), ['title' => 'Pengumuman', 'body' => 'Hasil pemilihan', 'confirmed' => 1])->assertSessionHasErrors('publish');
    $this->assertDatabaseCount('public_contents', 0);
});

it('refuses certification for inconsistent ballot and participation counts', function () {
    $this->freezeTime();
    kpumOpenElection();
    $candidate = kpumCandidate();
    DB::table('kpum_ballots')->insert(['id' => (string) Str::uuid(), 'election_id' => 1, 'candidate_id' => $candidate->id, 'cast_at' => now()]);
    KpumElection::findOrFail(1)->update(['status' => 'closed', 'closed_at' => now()]);
    $this->actingAs(kpumManager())->post(route('kpum.certify'), ['note' => 'Audit', 'confirmed' => 1])->assertSessionHasErrors('audit');
    $this->assertDatabaseHas('kpum_elections', ['id' => 1, 'status' => 'closed']);
});

it('publishes tied results without inventing a winner and prevents duplicate publication', function () {
    $this->freezeTime();
    $manager = kpumManager();
    $first = kpumCandidate();
    $second = kpumCandidate(['number' => 2, 'nim' => 'D0712002', 'name' => 'Kandidat Kedua']);
    KpumElection::findOrFail(1)->update(['status' => 'certified', 'period' => '2026/2027']);
    foreach ([$first, $second] as $candidate) {
        DB::table('kpum_ballots')->insert(['id' => (string) Str::uuid(), 'election_id' => 1, 'candidate_id' => $candidate->id, 'cast_at' => now()]);
    }
    $this->actingAs($manager)->post(route('kpum.publish'), ['title' => 'Pengumuman Hasil', 'body' => 'Hasil telah diperiksa.', 'confirmed' => 1])->assertSessionHasNoErrors();
    $content = PublicContent::first();
    expect(implode(' ', $content->payload['body']))->toContain('Hasil seri.');
    $this->post(route('kpum.publish'), ['title' => 'Pengumuman Hasil', 'body' => 'Hasil telah diperiksa.', 'confirmed' => 1])->assertSessionHasErrors('publish');
    $this->assertDatabaseCount('public_contents', 1);
});

it('completes the election lifecycle and publishes real totals', function () {
    $this->freezeTime();
    $manager = kpumManager();
    $candidate = kpumCandidate();
    kpumCandidate(['number' => 2, 'nim' => 'D0712002']);
    $voter = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    DB::table('kpum_electors')->insert(['election_id' => 1, 'user_id' => $voter->id]);
    KpumElection::findOrFail(1)->update(['period' => '2026/2027', 'starts_at' => now()->subMinute(), 'ends_at' => now()->addHour()]);
    $this->actingAs($manager)->post(route('kpum.open'))->assertSessionHasNoErrors();
    $this->actingAs($voter)->post(route('kpum.vote.submit'), ['candidate_id' => $candidate->id, 'confirmed' => 1])->assertSessionHasNoErrors();
    $this->actingAs($manager)->post(route('kpum.close'), ['reason' => 'Seluruh pemilih sudah memilih'])->assertSessionHasNoErrors();
    $this->post(route('kpum.certify'), ['note' => 'Data sesuai pemeriksaan panitia.', 'confirmed' => 1])->assertSessionHasNoErrors();
    $this->post(route('kpum.publish'), ['title' => 'Hasil KPUM', 'body' => 'Pemilihan selesai.', 'confirmed' => 1, 'winner_user_id' => $voter->id])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('kpum_elections', ['id' => 1, 'status' => 'published']);
    $this->assertDatabaseHas('member_appointments', ['user_id' => $voter->id, 'role' => 'ketua_umum', 'period_label' => '2026/2027', 'source_key' => 'election:1:2026/2027', 'ended_at' => null]);
    $this->get(route('announcement.detail', 'hasil-kpum-1'))->assertOk()->assertSee('Total 1 suara sah.');
});

it('converts WITA schedule input to application timezone without moving minutes', function () {
    $this->actingAs(kpumManager())->put(route('kpum.settings'), [
        'title' => 'Pemilihan', 'period' => '2026/2027',
        'starts_at' => '2026-12-01T08:00', 'ends_at' => '2026-12-01T15:30',
    ])->assertSessionHasNoErrors();
    expect(KpumElection::findOrFail(1)->starts_at->copy()->utc()->format('Y-m-d H:i:s'))->toBe('2026-12-01 00:00:00');
});

it('escapes candidate content instead of executing markup', function () {
    kpumCandidate(['name' => '<script>alert(1)</script>']);
    $this->actingAs(kpumManager())->get(route('kpum.candidates'))
        ->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('rejects a file pretending to be an allowed candidate photo', function () {
    Storage::fake('local');
    $this->actingAs(kpumManager())->post(route('kpum.candidates.store'), [
        'name' => 'Kandidat', 'nim' => 'D071001', 'number' => 1, 'angkatan' => 2024,
        'ipk' => 3.5, 'vision' => 'Visi', 'mission' => 'Misi',
        'photo' => UploadedFile::fake()->createWithContent('photo.png', '<?php echo 1;')->mimeType('text/x-php'),
    ])->assertSessionHasErrors('photo');
    $this->assertDatabaseCount('kpum_candidates', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('fails the audit for a ballot at or beyond the scheduled end', function () {
    $this->freezeTime();
    kpumOpenElection();
    $candidate = kpumCandidate();
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    DB::table('kpum_electors')->insert(['election_id' => 1, 'user_id' => $user->id, 'has_voted' => true]);
    DB::table('kpum_ballots')->insert(['id' => (string) Str::uuid(), 'election_id' => 1, 'candidate_id' => $candidate->id, 'cast_at' => now()->addHour()]);
    $this->travel(2)->hours();
    $this->actingAs(kpumManager())->post(route('kpum.certify'), ['note' => 'Audit', 'confirmed' => 1])->assertSessionHasErrors('audit');
});
