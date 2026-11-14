<?php

use App\Models\KbhIuranPembayaran;
use App\Models\KbhPendaftaranSeleksi;
use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\MemberDocument;
use App\Models\OpsGroup;
use App\Models\OpsMessage;
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

it('renders the password recovery form with its working submission endpoint', function (): void {
    $this->get(route('password.request'))->assertOk()->assertSee('Lupa Kata Sandi?')->assertSee(route('password.email'), false)->assertSee('hp-login-card');
});

it('allows archive metadata changes only for the document owner', function (): void {
    $owner = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $other = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $document = MemberDocument::factory()->create(['user_id' => $owner->id]);
    $this->actingAs($other)->put(route('member.archive.update', $document), ['title' => 'Stolen', 'category' => 'KTP'])->assertNotFound();
    $this->actingAs($owner)->put(route('member.archive.update', $document), ['title' => 'KRS terbaru', 'category' => 'Unknown'])->assertSessionHasErrors('category');
    $this->put(route('member.archive.update', $document), ['title' => 'KRS terbaru', 'category' => 'KHS', 'user_id' => $other->id, 'path' => 'elsewhere'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('member_documents', ['id' => $document->id, 'title' => 'KRS terbaru', 'category' => 'KHS', 'user_id' => $owner->id, 'path' => $document->path]);
});

it('filters and sorts private archives while keeping category totals scoped to their owner', function (): void {
    $owner = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    MemberDocument::factory()->create(['user_id' => $owner->id, 'title' => 'Zeta Record', 'created_at' => now()->subDays(2), 'category' => 'KRS']);
    MemberDocument::factory()->create(['user_id' => $owner->id, 'title' => 'Alpha Record', 'created_at' => now(), 'category' => 'KRS']);
    MemberDocument::factory()->create(['title' => 'Private stranger record', 'category' => 'KTP']);
    $this->actingAs($owner)->get(route('member.archive', ['sort' => 'name', 'view' => 'list', 'category' => 'KRS']))->assertOk()->assertSeeInOrder(['Alpha Record', 'Zeta Record'])->assertDontSee('Private stranger record')->assertViewHas('total', 2);
    $this->get(route('member.archive', ['sort' => 'oldest', 'q' => 'Record']))->assertSeeInOrder(['Zeta Record', 'Alpha Record']);
});

it('previews safe image files privately and rejects previews from a different account', function (): void {
    Storage::fake('local');
    $owner = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $photo = UploadedFile::fake()->image('identity.png');
    $path = $photo->store('member-archives/'.$owner->id, 'local');
    $document = MemberDocument::factory()->create(['user_id' => $owner->id, 'path' => $path, 'original_name' => 'identity.png', 'mime_type' => 'image/png']);
    $url = route('member.archive.file', ['upload', $document->id, 'file', 'preview' => 1]);
    $response = $this->actingAs($owner)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline;');
    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))->get($url)->assertNotFound();
});

it('searches messages only in the selected accessible group without marking filtered messages as read', function (): void {
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $group = OpsGroup::factory()->create(['department' => 'kbh']);
    $other = OpsGroup::factory()->create(['department' => 'humas']);
    OpsMessage::factory()->create(['group_id' => $group->id, 'body' => 'Rapat organisasi']);
    OpsMessage::factory()->create(['group_id' => $group->id, 'body' => 'Informasi berbeda']);
    OpsMessage::factory()->create(['group_id' => $other->id, 'body' => 'Rapat rahasia']);
    $response = $this->actingAs($member)->get(route('kbh.forum', ['group' => $group->id, 'search' => 'Rapat']))->assertOk()->assertSee('Rapat organisasi')->assertDontSee('Rapat rahasia')->assertDontSee('Keluar Grup');
    $response->assertViewHas('messages', fn ($messages): bool => $messages->count() === 1);
    $this->assertDatabaseMissing('ops_reads', ['group_id' => $group->id, 'user_id' => $member->id]);
});

it('uses the shared discussion layout for every authorized role', function (string $role): void {
    $user = User::factory()->create(['role' => $role, 'is_active' => true, 'department' => str_contains($role, 'departemen') ? 'humas' : null]);
    $this->actingAs($user)->get(route('department.forum', 'umum'))->assertOk()->assertSee('forum-workspace')->assertDontSee('Keluar Grup');
})->with(['super_admin', 'ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen', 'panitia_cb', 'panitia_kpum', 'kbh', 'koordinator_pengawas']);

it('keeps vote summaries private until publication and handles a tied result', function (): void {
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    foreach (['Kandidat Satu', 'Kandidat Dua'] as $index => $name) {
        $candidate = KpumCandidate::create(['election_id' => 1, 'number' => $index + 1, 'name' => $name, 'nim' => 'D042400'.$index, 'angkatan' => 2024, 'ipk' => 3.5, 'vision' => 'Visi kandidat', 'mission' => 'Misi kandidat', 'status' => 'approved']);
        DB::table('kpum_ballots')->insert(['election_id' => 1, 'candidate_id' => $candidate->id, 'id' => (string) Str::uuid(), 'cast_at' => now()]);
    }
    $this->actingAs($member)->get(route('kbh.voting.hasil'))->assertOk()->assertDontSee('Grafik Hasil Voting Resmi')->assertDontSee('Perolehan suara resmi');
    KpumElection::findOrFail(1)->update(['status' => 'published']);
    $this->get(route('kbh.voting.hasil'))->assertOk()->assertSee('Grafik Hasil Voting Resmi')->assertSee('Perolehan tertinggi sama')->assertSee('50%')->assertSee('Visi &amp; Misi Calon Ketua Umum', false);
});

it('renders a published election without votes without inventing a winner', function (): void {
    KpumElection::findOrFail(1)->update(['status' => 'published']);
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))->get(route('kbh.voting.hasil'))->assertOk()->assertSee('Belum ada pemenang');
});

it('renders selection history with the actual interview status', function (): void {
    $application = KbhPendaftaranSeleksi::factory()->create(['status' => 'wawancara', 'interview_at' => now()->addDay(), 'interview_location' => 'Ruang Seleksi Uji', 'reviewed_at' => now()]);
    $this->actingAs($application->user)->get(route('kbh.seleksi.index'))->assertOk()->assertSee('Riwayat Seleksi')->assertSee('Ruang Seleksi Uji')->assertSee('Hasil akhir akan ditampilkan setelah dipublikasikan oleh panitia.');
});

it('stores optional payment notes while retaining verification and duplicate submission protections', function (): void {
    Storage::fake('local');
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $this->actingAs($member)->get(route('kbh.iuran.index'))->assertOk()->assertSee('duesUpload')->assertSee('catatan_anggota');
    $data = ['tahun' => 2026, 'konfirmasi' => 1, 'catatan_anggota' => 'Iuran bulan November', 'status' => 'lunas', 'bukti_transfer' => UploadedFile::fake()->create('transfer.pdf', 3, 'application/pdf')];
    $this->post(route('kbh.iuran.store', ['bulan' => 11]), $data)->assertSessionHasNoErrors();
    $payment = KbhIuranPembayaran::where('user_id', $member->id)->firstOrFail();
    expect($payment->status)->toBe('menunggu_verifikasi');
    expect($payment->catatan_anggota)->toBe('Iuran bulan November');
    $this->post(route('kbh.iuran.store', ['bulan' => 11]), array_replace($data, ['catatan_anggota' => 'Changed']))->assertSessionHasErrors('bukti_transfer');
    expect($payment->fresh()->catatan_anggota)->toBe('Iuran bulan November');
    $this->post(route('kbh.iuran.store', ['bulan' => 12]), array_replace($data, ['catatan_anggota' => str_repeat('x', 201)]))->assertSessionHasErrors('catatan_anggota');
});

it('places the member archive after the forum and keeps the dashboard first', function (): void {
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))->get(route('kbh.dashboard'))->assertOk()->assertSeeInOrder(['href="'.route('kbh.dashboard').'"', 'href="'.route('kbh.forum').'"', 'href="'.route('member.archive').'"'], false);
});
