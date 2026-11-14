<?php

use App\Models\OfficerReport;
use App\Models\OpsProgram;
use App\Models\User;
use App\Support\Governance;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(LazilyRefreshDatabase::class);
beforeEach(function (): void {
    $this->withoutVite();
});
function oldGovernancePeriod(): void
{
    DB::table('governance_periods')->insert(['name' => 'Lama', 'starts_at' => now()->subYears(2), 'ends_at' => now()->subYear(), 'closed_at' => now()->subYear(), 'created_at' => now()->subYears(2), 'updated_at' => now()]);
    DB::table('governance_periods')->insert(['name' => 'Baru', 'starts_at' => now()->subDay(), 'ends_at' => now()->addYear(), 'created_at' => now(), 'updated_at' => now()]);
}
it('expires officer appointments once while retaining student accounts', function (): void {
    $user = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    DB::table('governance_periods')->insert(['name' => 'Lama', 'starts_at' => now()->subYear()->subDay(), 'ends_at' => now()->subDay(), 'created_at' => now(), 'updated_at' => now()]);
    Governance::expire();
    Governance::expire();
    expect($user->fresh()->assignedRole())->toBe('kbh');
    expect(DB::table('governance_periods')->whereNotNull('closed_at')->count())->toBe(1);
    expect(DB::table('security_events')->where('event', 'governance_closed')->count())->toBe(1);
});
it('protects old records from model and bulk changes but permits new records', function (): void {
    $record = OpsProgram::factory()->create(['created_at' => now()->subYears(2)]);
    oldGovernancePeriod();
    try {
        $record->update(['title' => 'Changed']);
        $this->fail('Expected locked record');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(423);
    }
    try {
        OpsProgram::whereKey($record->id)->delete();
        $this->fail('Expected locked bulk delete');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(423);
    }
    expect(OpsProgram::find($record->id))->not->toBeNull();
    $new = OpsProgram::factory()->create();
    $new->update(['title' => 'New work']);
    expect($new->fresh()->title)->toBe('New work');
});
it('lets successors read departmental archives and append separate followups', function (): void {
    $old = OpsProgram::factory()->create(['department' => 'akademik', 'created_at' => now()->subYears(2)]);
    oldGovernancePeriod();
    $user = User::factory()->create(['role' => 'koordinator_departemen', 'department' => 'akademik', 'is_active' => true]);
    $this->actingAs($user)->get(route('governance.archive', ['type' => 'program']))->assertOk()->assertSee('Hanya baca');
    $this->post(route('governance.followup', ['program', $old->id]), ['note' => 'Lanjutkan koordinasi'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('governance_followups', ['source_id' => $old->id, 'author_id' => $user->id, 'note' => 'Lanjutkan koordinasi']);
    $other = User::factory()->create(['role' => 'koordinator_departemen', 'department' => 'humas', 'is_active' => true]);
    $this->actingAs($other)->post(route('governance.followup', ['program', $old->id]), ['note' => 'Tidak boleh'])->assertNotFound();
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))->get(route('governance.archive'))->assertForbidden();
});
it('allows admins to configure one annual period and rejects overlap', function (): void {
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($admin)->post(route('admin.governance.store'), ['name' => '2026/2027', 'starts_at' => now()->toDateString()])->assertSessionHasNoErrors();
    expect(DB::table('governance_periods')->value('ends_at'))->toBe(now()->startOfDay()->addYearNoOverflow()->toDateTimeString());
    $this->post(route('admin.governance.store'), ['name' => 'Duplikat', 'starts_at' => now()->toDateString()])->assertStatus(422);
});

it('serves archived files to the successor but refuses other departments', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('reports/old.pdf', 'test');
    $old = OfficerReport::factory()->create(['role' => 'sekretaris_umum', 'file_path' => 'reports/old.pdf', 'created_at' => now()->subYears(2)]);
    oldGovernancePeriod();
    $user = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    $url = route('governance.file', ['laporan', $old->id, 'file_path']);
    $this->actingAs($user)->get($url)->assertDownload('old.pdf');
    $this->actingAs(User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]))->get($url)->assertNotFound();
});
it('closes expired appointments before serving the next protected request', function (): void {
    $user = User::factory()->create(['role' => 'sekretaris_umum', 'is_active' => true]);
    DB::table('governance_periods')->insert(['name' => 'Lama', 'starts_at' => now()->subYear()->subDay(), 'ends_at' => now()->subDay(), 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs($user)->get(route('sekretaris.dashboard'))->assertForbidden();
    expect($user->fresh()->assignedRole())->toBe('kbh');
});

it('blocks granting a new officer role between governance periods', function (): void {
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    DB::table('governance_periods')->insert(['name' => 'Lama', 'starts_at' => now()->subYears(2), 'ends_at' => now()->subYear(), 'closed_at' => now()->subYear(), 'created_at' => now(), 'updated_at' => now()]);
    try {
        $user->forceFill(['role' => 'sekretaris_umum'])->save();
        $this->fail('Expected inactive period');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(423);
    }
    expect($user->fresh()->assignedRole())->toBe('kbh');
});
