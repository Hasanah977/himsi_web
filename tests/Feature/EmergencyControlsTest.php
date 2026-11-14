<?php

use App\Models\PublicContent;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\DatabaseRecovery;
use App\Support\EmergencyState;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(getenv('DB_CONNECTION') === 'mysql' ? DatabaseMigrations::class : LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    config(['security.directory' => storage_path('framework/testing/security-'.Str::uuid())]);
});

function emergencyAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
}

it('closes every public page with a static maintenance response and keeps admin and login available', function () {
    $admin = emergencyAdmin();
    $this->actingAs($admin)->post(route('admin.security.maintenance'), ['enabled' => 1])->assertRedirect();
    foreach (['/', '/profil', '/struktur', '/program-kerja', '/program-kerja/anything', '/pengumuman', '/pengumuman/anything', '/daftar'] as $path) {
        $this->get($path)->assertStatus(503)->assertSee('Sedang Dalam Perbaikan')->assertHeader('Retry-After', '300');
    }
    $this->get(route('admin.security.index'))->assertOk()->assertSee('Publik ditutup');
    Auth::logout();
    $this->get(route('login'))->assertOk();
    $this->actingAs($admin)->post(route('admin.security.maintenance'), ['enabled' => 0])->assertRedirect();
    expect(app(EmergencyState::class)->read()['maintenance'])->toBeFalse();
});

it('denies all other roles access to emergency controls and records the denial', function (string $role) {
    $user = User::factory()->create(['role' => $role, 'is_active' => true, 'department' => 'humas']);
    $this->actingAs($user)->post(route('admin.security.freeze'))->assertForbidden();
    expect(app(EmergencyState::class)->read()['frozen'])->toBeFalse();
    expect(SecurityEvent::where('event', 'access_denied')->where('severity', 'danger')->where('actor_id', $user->id)->exists())->toBeTrue();
})->with(['ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen', 'panitia_cb', 'panitia_kpum', 'kbh', 'koordinator_pengawas']);

it('requires authentication for security controls', function () {
    $this->get(route('admin.security.index'))->assertRedirect(route('login'));
});

it('toggles the dashboard public access button label color and submitted action', function (): void {
    $this->actingAs(emergencyAdmin());
    $dashboard = route('admin.dashboard');
    foreach ([false, true, false] as $closed) {
        $this->from($dashboard)->post(route('admin.security.maintenance'), ['enabled' => (int) $closed])->assertRedirect($dashboard);
        $response = $this->get($dashboard)->assertOk();
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $form = '//form[@action="'.route('admin.security.maintenance').'"]';
        expect($xpath->evaluate('string('.$form.'//input[@name="enabled"]/@value)'))->toBe($closed ? '0' : '1');
        expect(trim($xpath->evaluate('string('.$form.'//button)')))->toBe($closed ? 'Buka publik' : 'Tutup publik');
        expect($xpath->evaluate('string('.$form.'//button/@class)'))->toBe($closed ? 'btn btn-success' : 'btn btn-danger');
        expect(app(EmergencyState::class)->read()['maintenance'])->toBe($closed);
    }
});

it('revokes other sessions and remember tokens while preserving Super Admin and inactive accounts', function () {
    $admin = emergencyAdmin();
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $inactive = User::factory()->create(['role' => 'bendahara_umum', 'is_active' => false]);
    config(['session.driver' => 'database']);
    foreach ([$admin, $member] as $user) {
        DB::table('sessions')->insert(['id' => 'session-'.$user->id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    }
    $token = $admin->remember_token;
    $this->actingAs($admin)->post(route('admin.security.freeze'))->assertRedirect();
    expect(DB::table('sessions')->where('id', 'session-'.$member->id)->exists())->toBeFalse();
    expect(DB::table('sessions')->where('id', 'session-'.$admin->id)->exists())->toBeTrue();
    expect($member->fresh()->remember_token)->toBeNull();
    expect($admin->fresh()->remember_token)->toBe($token);
    $this->get(route('admin.security.index'))->assertOk();
    $this->post(route('admin.security.unfreeze'))->assertRedirect();
    expect($inactive->fresh()->is_active)->toBeFalse();
    $this->actingAs($member)->withSession(['security_generation' => 0])->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('blocks non-admin login during freeze and permits a new login after reopening', function () {
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true, 'welcome_signature' => 'kbh:']);
    app(EmergencyState::class)->change(['frozen' => true], true);
    $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
    app(EmergencyState::class)->change(['frozen' => false]);
    $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    $this->get(route('dashboard'))->assertRedirect(route('kbh.dashboard'));
});

it('permits Super Admin login during both emergency modes', function () {
    $admin = emergencyAdmin();
    app(EmergencyState::class)->change(['frozen' => true, 'maintenance' => true], true);
    $this->post(route('login.submit'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    $this->get(route('admin.security.index'))->assertOk();
});

it('flags five failed logins without storing passwords and resets the streak after success', function () {
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'secret-do-not-log'])->assertSessionHasErrors('email');
    }
    $alert = SecurityEvent::where('event', 'login_failed')->latest('id')->first();
    expect($alert->severity)->toBe('danger')->and($alert->context['attempts'])->toBe(5);
    expect(SecurityEvent::all()->toJson())->not->toContain('secret-do-not-log');
    $this->travel(61)->seconds();
    $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    Auth::logout();
    $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    expect(SecurityEvent::where('event', 'login_failed')->latest('id')->first()->context['attempts'])->toBe(1);
});

it('creates a private encrypted backup and restores data while retaining admin credentials and audit', function () {
    Storage::fake('local');
    $admin = emergencyAdmin();
    $member = User::factory()->create(['role' => 'kbh', 'name' => 'Before restore']);
    $this->actingAs($admin)->post(route('admin.security.backup'))->assertRedirect()->assertSessionHasNoErrors();
    $backup = app(DatabaseRecovery::class)->listing()[0];
    expect(Storage::disk('local')->get('security/backups/'.$backup['id'].'.enc'))->not->toContain('Before restore');
    $member->forceFill(['name' => 'Changed'])->save();
    $admin->forceFill(['name' => 'Admin preserved', 'password' => Hash::make('new-password')])->save();
    SecurityEvent::factory()->danger()->create(['summary' => 'Keep this audit']);
    $this->get(route('admin.security.restore.review', $backup['id']))->assertOk()->assertSee('Pulihkan database');
    $this->post(route('admin.security.restore', $backup['id']), ['password' => 'new-password', 'confirm_restore' => 1])->assertRedirect(route('admin.security.index'))->assertSessionHasNoErrors();
    expect($member->fresh()->name)->toBe('Before restore');
    expect($admin->fresh()->name)->toBe('Admin preserved');
    expect(Hash::check('new-password', $admin->fresh()->password))->toBeTrue();
    expect(SecurityEvent::where('summary', 'Keep this audit')->exists())->toBeTrue();
    expect(app(DatabaseRecovery::class)->listing())->toHaveCount(2);
    expect(app(EmergencyState::class)->read())->toMatchArray(['frozen' => true, 'maintenance' => true]);
});

it('refuses restore without current password and explicit confirmation', function () {
    Storage::fake('local');
    $admin = emergencyAdmin();
    $backup = app(DatabaseRecovery::class)->create();
    $this->actingAs($admin)->post(route('admin.security.restore', $backup['id']), ['password' => 'wrong', 'confirm_restore' => 1])->assertSessionHasErrors('password');
    $this->post(route('admin.security.restore', $backup['id']), ['password' => 'password'])->assertSessionHasErrors('confirm_restore');
    expect(app(EmergencyState::class)->read()['frozen'])->toBeFalse();
});

it('rejects a tampered backup before changing data or emergency state', function () {
    Storage::fake('local');
    $admin = emergencyAdmin();
    $backup = app(DatabaseRecovery::class)->create();
    Storage::disk('local')->put('security/backups/'.$backup['id'].'.enc', 'tampered');
    $this->actingAs($admin)->post(route('admin.security.restore', $backup['id']), ['password' => 'password', 'confirm_restore' => 1])->assertSessionHasErrors('backup');
    expect(User::count())->toBe(1);
    expect(app(EmergencyState::class)->read()['maintenance'])->toBeFalse();
});

it('keeps restore and backup downloads restricted to Super Admin', function () {
    Storage::fake('local');
    emergencyAdmin();
    $user = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $backup = app(DatabaseRecovery::class)->create();
    $this->actingAs($user)->get(route('admin.security.download', $backup['id']))->assertForbidden();
    $this->get(route('admin.security.restore.review', $backup['id']))->assertForbidden();
    $this->post(route('admin.security.restore', $backup['id']), ['password' => 'password', 'confirm_restore' => 1])->assertForbidden();
});

it('filters dangerous events and escapes account names in the audit view', function () {
    $admin = emergencyAdmin();
    SecurityEvent::factory()->danger()->create(['actor_name' => '<script>alert(1)</script>', 'summary' => 'Unauthorized test']);
    SecurityEvent::factory()->create(['summary' => 'Ordinary activity']);
    $this->actingAs($admin)->get(route('admin.security.logs', ['severity' => 'danger']))->assertOk()->assertSee('Unauthorized test')->assertDontSee('Ordinary activity')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

it('provides governance control without the removed role navigation group', function () {
    $this->actingAs(emergencyAdmin())->get(route('admin.dashboard'))->assertOk()->assertDontSee('admin-role-group', false)->assertSee('Kontrol Kepengurusan')->assertDontSee('MODUL BERDASARKAN ROLE');
});

it('flags a bulk delete with its affected table and row count', function () {
    $admin = emergencyAdmin();
    $users = User::factory()->count(10)->create(['role' => 'kbh']);
    Route::middleware('web')->delete('/test-bulk-delete', function () use ($users) {
        DB::table('users')->whereIn('id', $users->modelKeys())->delete();

        return response()->noContent();
    })->name('testing.bulk-delete');
    $this->actingAs($admin)->delete('/test-bulk-delete')->assertNoContent();
    $alert = SecurityEvent::where('event', 'data_deleted')->first();
    expect($alert)->not->toBeNull();
    expect($alert->severity)->toBe('danger')->and($alert->affected_count)->toBe(10)->and($alert->context['table'])->toBe('users');
});

it('restores a missing table and its data from the verified snapshot', function () {
    Storage::fake('local');
    $admin = emergencyAdmin();
    $content = PublicContent::create(['section' => 'announcements', 'slug' => 'restore-content', 'payload' => ['title' => 'Announcement restored'], 'is_published' => true]);
    $backup = app(DatabaseRecovery::class)->create();
    Schema::withoutForeignKeyConstraints(fn () => Schema::drop('public_contents'));
    $this->actingAs($admin)->get(route('admin.security.index'))->assertOk();
    $this->get(route('admin.security.restore.review', $backup['id']))->assertOk();
    $this->actingAs($admin)->post(route('admin.security.restore', $backup['id']), ['password' => 'password', 'confirm_restore' => 1])->assertRedirect(route('admin.security.index'))->assertSessionHasNoErrors();
    expect(PublicContent::find($content->id)->payload['title'])->toBe('Announcement restored');
});

it('refuses an outdated schema snapshot without replacing existing rows', function () {
    Storage::fake('local');
    $admin = emergencyAdmin();
    $backup = app(DatabaseRecovery::class)->create();
    DB::table('migrations')->insert(['migration' => 'future_schema', 'batch' => 99]);
    $this->actingAs($admin)->post(route('admin.security.restore', $backup['id']), ['password' => 'password', 'confirm_restore' => 1])->assertSessionHasErrors('backup');
    expect(DB::table('migrations')->where('migration', 'future_schema')->exists())->toBeTrue();
    expect(app(EmergencyState::class)->read()['frozen'])->toBeFalse();
});

it('rolls back restored rows if validation finds a broken relationship', function () {
    Storage::fake('local');
    $admin = emergencyAdmin();
    $member = User::factory()->create(['role' => 'kbh', 'name' => 'Original member']);
    $backup = app(DatabaseRecovery::class)->create();
    $payload = app(DatabaseRecovery::class)->read($backup['id']);
    $payload['tables']['kpum_electors']['rows'][] = ['id' => 999, 'election_id' => 1, 'user_id' => 999999, 'has_voted' => 0];
    Storage::disk('local')->put('security/backups/'.$backup['id'].'.enc', Crypt::encryptString(base64_encode(gzencode(json_encode($payload)))));
    $member->forceFill(['name' => 'Keep current member'])->save();
    $this->actingAs($admin)->post(route('admin.security.restore', $backup['id']), ['password' => 'password', 'confirm_restore' => 1])->assertSessionHasErrors('backup');
    expect($member->fresh()->name)->toBe('Keep current member');
    expect(app(EmergencyState::class)->read()['frozen'])->toBeTrue();
    expect(app(DatabaseRecovery::class)->listing())->toHaveCount(2);
});
