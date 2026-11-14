<?php

use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\OpsProgram;
use App\Models\PublicContent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('ends the selected period and removes every officer appointment while preserving accounts and archives', function (): void {
    $this->travelTo(now()->setDate(2026, 10, 30)->setTime(12, 0));
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $officers = collect(User::OFFICER_ROLES)->map(fn (string $role) => User::factory()->create(['role' => $role, 'department' => 'humas', 'is_active' => true]));
    $committee = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);
    $program = OpsProgram::factory()->create(['pic_id' => $officers[3]->id, 'created_at' => now()->subDay()]);
    $period = DB::table('governance_periods')->insertGetId(['name' => '2026/2027', 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(11), 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($admin)->get(route('admin.governance.index'))->assertOk()->assertSee('Akhiri Kepengurusan')->assertSee('5 pengurus');
    $this->post(route('admin.governance.end', $period), ['confirmed' => 1])->assertRedirect(route('admin.governance.index'))->assertSessionHasNoErrors();

    foreach ($officers as $officer) {
        expect($officer->fresh()->assignedRole())->toBe('kbh');
        expect($officer->fresh()->department)->toBeNull();
        expect($officer->fresh()->is_active)->toBeTrue();
    }
    expect($committee->fresh()->assignedRole())->toBe('panitia_kpum');
    $this->assertDatabaseHas('governance_periods', ['id' => $period, 'ends_at' => '2026-10-30 12:00:00', 'closed_at' => '2026-10-30 12:00:00']);
    $this->assertModelExists($program);
    $this->get(route('governance.archive', ['type' => 'program']))->assertSee($program->title);
    $this->post(route('admin.governance.end', $period), ['confirmed' => 1])->assertUnprocessable();
    expect(DB::table('security_events')->where('event', 'governance_closed')->count())->toBe(1);
    $this->actingAs($officers[1]->fresh())->get(route('sekretaris.dashboard'))->assertForbidden();
    $this->get(route('kbh.dashboard'))->assertOk();

    $this->actingAs($admin)->post(route('admin.governance.store'), ['name' => 'Periode pengganti', 'starts_at' => '2026-10-30'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('governance_periods', ['name' => 'Periode pengganti', 'starts_at' => '2026-10-30 12:00:00', 'closed_at' => null]);
});

it('requires super admin access and explicit confirmation to end a period', function (): void {
    $this->freezeTime();
    $period = DB::table('governance_periods')->insertGetId(['name' => 'Aktif', 'starts_at' => now()->subDay(), 'ends_at' => now()->addYear(), 'created_at' => now(), 'updated_at' => now()]);
    $url = route('admin.governance.end', $period);
    $this->post($url, ['confirmed' => 1])->assertRedirect(route('login'));
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($member)->post($url, ['confirmed' => 1])->assertForbidden();
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($admin)->post($url)->assertSessionHasErrors('confirmed');
    $this->post(route('admin.governance.end', 99999), ['confirmed' => 1])->assertUnprocessable();
    $this->assertDatabaseHas('governance_periods', ['id' => $period, 'closed_at' => null]);
    $this->assertDatabaseMissing('security_events', ['event' => 'governance_closed']);
});

it('keeps voting and published results in one member menu without exposing early totals', function (): void {
    $this->freezeTime();
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $candidate = KpumCandidate::create(['election_id' => 1, 'number' => 1, 'name' => 'Calon Pilihan', 'nim' => 'D07001', 'angkatan' => 2024, 'ipk' => 3.5, 'vision' => 'Visi bersama', 'mission' => 'Misi bersama', 'status' => 'approved']);
    KpumElection::findOrFail(1)->update(['status' => 'open', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
    DB::table('kpum_electors')->insert(['election_id' => 1, 'user_id' => $user->id]);

    $this->actingAs($user)->get(route('kbh.voting.hasil'))->assertSee('Calon Pilihan')->assertSee('Pilih Kandidat')->assertDontSee('Voting Ketua');
    $this->post(route('kbh.voting.vote'), ['candidate_id' => $candidate->id, 'confirmed' => 1])->assertRedirect(route('kbh.voting.hasil'))->assertSessionHasNoErrors();
    $this->get(route('kbh.voting.hasil'))->assertSee('Hak pilih Anda sudah digunakan')->assertDontSee('Pilih Kandidat')->assertDontSee('1 suara');
    KpumElection::findOrFail(1)->update(['status' => 'certified', 'closed_at' => now()]);
    $this->get(route('kbh.voting.hasil'))->assertSee('Hasil belum dipublikasikan')->assertDontSee('1 suara');
    KpumElection::findOrFail(1)->update(['status' => 'published']);
    $this->get(route('kbh.voting.hasil'))->assertSee('1 suara')->assertDontSee('Pilih Kandidat');
    $this->get(route('kbh.voting.index'))->assertSee('1 suara');
    $this->assertDatabaseHas('kbh_activity_reads', ['user_id' => $user->id, 'route' => 'kbh.voting.hasil']);
});

it('publishes the uploaded winner photo on public and member announcements and refuses duplicates', function (): void {
    Storage::fake('public');
    $this->freezeTime();
    $manager = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);
    $winner = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $candidate = KpumCandidate::create(['election_id' => 1, 'user_id' => $winner->id, 'number' => 1, 'name' => $winner->name, 'nim' => 'D07001', 'angkatan' => 2024, 'ipk' => 3.5, 'vision' => 'Visi', 'mission' => 'Misi', 'status' => 'approved']);
    DB::table('kpum_ballots')->insert(['id' => (string) Str::uuid(), 'election_id' => 1, 'candidate_id' => $candidate->id, 'cast_at' => now()]);
    KpumElection::findOrFail(1)->update(['status' => 'certified']);
    $payload = ['title' => 'Hasil Pemilihan', 'body' => 'Selamat kepada ketua terpilih.', 'confirmed' => 1, 'winner_photo' => UploadedFile::fake()->image('winner.png')];

    $this->actingAs($manager)->get(route('kpum.announcement'))->assertOk()->assertSee('Foto pemenang');
    $this->post(route('kpum.publish'), $payload)->assertSessionHasNoErrors();

    $announcement = PublicContent::where('slug', 'hasil-kpum-1')->firstOrFail();
    $photo = $announcement->payload['image'];
    Storage::disk('public')->assertExists(Str::after($photo, 'storage/'));
    $this->get(route('announcement.detail', $announcement->slug))->assertOk()->assertSee(asset($photo), false);
    $this->get(asset($photo))->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))->get(route('kbh.pengumuman.show', $announcement->slug))->assertOk()->assertSee(asset($photo), false);
    expect($winner->fresh()->assignedRole())->toBe('ketua_umum');
    $this->actingAs($manager)->post(route('kpum.publish'), $payload)->assertSessionHasErrors('publish');
    expect(Storage::disk('public')->allFiles())->toHaveCount(1);
});

it('rejects unsafe or oversized winner photos without publishing or storing files', function (string $kind): void {
    Storage::fake('public');
    $manager = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);
    KpumElection::findOrFail(1)->update(['status' => 'certified']);
    $file = match ($kind) {
        'oversized' => UploadedFile::fake()->image('large.jpg')->size(5121),
        'gif' => UploadedFile::fake()->image('animated.gif'),
        default => UploadedFile::fake()->createWithContent('photo.png', '<?php echo 1;')->mimeType('text/x-php'),
    };

    $this->actingAs($manager)->post(route('kpum.publish'), ['title' => 'Hasil', 'body' => 'Pengumuman', 'confirmed' => 1, 'winner_photo' => $file])->assertSessionHasErrors('winner_photo');

    expect(Storage::disk('public')->allFiles())->toBe([]);
    $this->assertDatabaseMissing('public_contents', ['slug' => 'hasil-kpum-1']);
    $this->assertDatabaseHas('kpum_elections', ['id' => 1, 'status' => 'certified']);
})->with(['oversized', 'gif', 'disguised']);

it('serves only supported public image paths and returns not found for missing images', function (): void {
    Storage::fake('public');
    Storage::fake('local');
    Storage::disk('public')->put('himsi/report.pdf', 'private document');
    Storage::disk('local')->put('secret.png', 'private image');

    $this->get('/storage/himsi/missing.png')->assertNotFound();
    $this->get('/storage/himsi/report.pdf')->assertForbidden();
    $this->get('/storage/himsi/secret.png')->assertNotFound();
    $this->get('/storage/himsi/subfolder/secret.png')->assertForbidden();

    Storage::disk('local')->assertExists('secret.png');
    Storage::disk('public')->assertExists('himsi/report.pdf');
});
