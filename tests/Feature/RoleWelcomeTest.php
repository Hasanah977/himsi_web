<?php

use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\OpsProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('greets each eligible role once and greets again after a role change', function (string $role) {
    $user = User::factory()->create(['role' => $role, 'is_active' => true, 'department' => in_array($role, ['anggota_departemen', 'koordinator_departemen']) ? 'humas' : null]);
    $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('role.welcome'));
    $this->get(route('role.welcome'))->assertOk()->assertSee('Selamat,')->assertSee(config('superadmin.roles.'.$role))->assertSee('celebration-confetti')->assertDontSee('Persentase');
    $this->post(route('role.welcome.acknowledge'))->assertRedirect();
    $this->get(route('role.welcome'))->assertRedirect(route('dashboard'));
    $this->post(route('logout'));
    $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    $user->forceFill(['role' => $role === 'kbh' ? 'sekretaris_umum' : 'kbh'])->save();
    $this->post(route('logout'));
    $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('role.welcome'));
})->with(['ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'anggota_departemen', 'koordinator_departemen', 'kbh']);

it('shows election statistics only for the linked elected chair', function () {
    $this->freezeTime();
    $user = User::factory()->create(['role' => 'ketua_umum', 'is_active' => true]);
    KpumElection::findOrFail(1)->update(['status' => 'published', 'period' => '2026/2027']);
    $candidate = KpumCandidate::create(['election_id' => 1, 'user_id' => $user->id, 'number' => 1, 'name' => $user->name, 'nim' => 'D0123', 'angkatan' => 2024, 'ipk' => 3.5, 'vision' => 'Visi', 'mission' => 'Misi', 'status' => 'approved']);
    DB::table('kpum_ballots')->insert(['id' => (string) Str::uuid(), 'election_id' => 1, 'candidate_id' => $candidate->id, 'cast_at' => now()]);
    DB::table('kpum_electors')->insert(['election_id' => 1, 'user_id' => $user->id, 'has_voted' => true]);
    $this->actingAs($user)->get(route('role.welcome'))->assertOk()->assertSee('100%')->assertSee('2026/2027')->assertSee('Pemilih')->assertSee('Suara');
    $this->actingAs(User::factory()->create(['role' => 'ketua_umum', 'is_active' => true]))->get(route('role.welcome'))->assertOk()->assertDontSee('>100%<', false)->assertDontSee('Persentase');
});

it('bulk registers all active KBH across pages and blocks other roles from voting even with legacy electors', function () {
    $this->freezeTime();
    $manager = User::factory()->create(['role' => 'panitia_kpum', 'is_active' => true]);
    $members = User::factory()->count(25)->create(['role' => 'kbh', 'is_active' => true]);
    $secretary = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    User::factory()->create(['role' => 'kbh', 'is_active' => false]);
    $this->actingAs($manager)->get(route('kpum.voters'))->assertOk()->assertSee('Pilih Semua KBH Aktif')->assertDontSee($secretary->email);
    $this->post(route('kpum.voters.all'))->assertSessionHasNoErrors();
    $this->post(route('kpum.voters.all'))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('kpum_electors', 25);
    $this->post(route('kpum.voters.update', $secretary), ['include' => 1])->assertUnprocessable();
    DB::table('kpum_electors')->insert(['election_id' => 1, 'user_id' => $secretary->id]);
    $this->actingAs($secretary)->get(route('kpum.vote'))->assertForbidden();
    $this->post(route('kpum.vote.submit'), ['candidate_id' => 1, 'confirmed' => 1])->assertForbidden();
    KpumElection::findOrFail(1)->update(['status' => 'open', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
    $this->actingAs($manager)->post(route('kpum.voters.all'))->assertSessionHasErrors('kpum');
    $this->assertDatabaseCount('kpum_ballots', 0);
});

it('shows real department statistics and separate escaped vision and mission', function () {
    $chair = User::factory()->create(['role' => 'ketua_umum', 'is_active' => true]);
    OpsProgram::factory()->create(['department' => 'humas', 'status' => 'completed']);
    $this->actingAs($chair)->get(route('ketua-umum.dashboard'))->assertOk()->assertSee('Program Kerja per Departemen')->assertSee('1 / 1 selesai')->assertDontSee('Akses Cepat');
    KpumCandidate::create(['election_id' => 1, 'number' => 1, 'name' => 'Calon Ketua', 'nim' => 'D0123', 'angkatan' => 2024, 'ipk' => 3.5, 'vision' => '<script>visi</script>', 'mission' => 'Misi nyata', 'status' => 'approved']);
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))->get(route('kbh.voting.index'))->assertOk()->assertSee('Visi')->assertSee('Misi nyata')->assertSee('&lt;script&gt;visi&lt;/script&gt;', false)->assertDontSee('<script>visi</script>', false);
});
