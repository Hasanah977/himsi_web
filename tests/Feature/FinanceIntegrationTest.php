<?php

use App\Models\FinanceEntry;
use App\Models\KbhIuranPembayaran;
use App\Models\NotifikasiHimsi;
use App\Models\OpsSubmission;
use App\Models\User;
use App\Support\FinanceLedger as Ledger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(now()->setDate(2026, 10, 22)->setTime(12, 0));
    Storage::fake('local');
});
function financeUser(string $role = 'bendahara_umum'): User
{
    return User::factory()->create(['role' => $role, 'is_active' => true, 'department' => str_contains($role, 'departemen') ? 'kaderisasi' : null]);
}
function financeData(array $override = []): array
{
    return array_replace(['reference' => (string) Str::uuid(), 'occurred_on' => '2026-10-20', 'amount' => 1000000, 'category' => 'Donasi', 'method' => 'Transfer', 'description' => 'Dana organisasi'], $override);
}
function financeCash(int $amount = 1000000): void
{
    DB::transaction(fn () => Ledger::record(['reference' => (string) Str::uuid(), 'occurred_on' => '2026-05-01', 'amount' => $amount, 'category' => 'Saldo Awal', 'method' => 'Tunai', 'description' => 'Dana awal', 'kind' => 'income', 'origin' => 'manual']));
}
function financeBudget(array $override = []): OpsSubmission
{
    return OpsSubmission::factory()->create(array_replace(['type' => 'budget', 'status' => 'pending_chair', 'version' => 1, 'total' => 200000, 'payload' => ['items' => [['name' => 'Konsumsi', 'qty' => 10, 'unit' => 'paket', 'price' => 20000]]]], $override));
}
function financePayment(OpsSubmission $s, array $override = []): array
{
    return array_replace(['version' => $s->fresh()->version, 'occurred_on' => '2026-10-22', 'receipt' => UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf')], $override);
}
it('renders treasury pages and read-only superadmin previews', function ($role, $route, $params) {
    $this->actingAs(financeUser($role))->get(route($route, $params))->assertOk();
})->with(['bendahara_umum', 'super_admin'])->with([
    ['finance.dashboard', []], ['finance.entries', ['income']], ['finance.entries', ['expense']], ['finance.funding', []], ['finance.billing', []], ['finance.report', []], ['finance.monitoring', []], ['finance.logs', []], ['bendahara.iuran.index', []],
]);
it('blocks unauthorized roles and all superadmin writes', function ($role) {
    $this->actingAs(financeUser($role))->post(route('finance.store', 'income'), financeData())->assertForbidden();
    expect(FinanceEntry::count())->toBe(0);
    if ($role !== 'super_admin') {
        $this->get(route('finance.dashboard'))->assertForbidden();
    }
})->with(['kbh', 'ketua_umum', 'sekretaris_umum', 'koordinator_departemen', 'anggota_departemen', 'panitia_cb', 'panitia_kpum', 'koordinator_pengawas', 'super_admin']);
it('redirects guests and routes the treasurer dashboard', function () {
    $this->get(route('finance.dashboard'))->assertRedirect(route('login'));
    $this->actingAs(financeUser())->get(route('dashboard'))->assertRedirect(route('finance.dashboard'));
});
it('records manual transactions once and protects historical solvency', function () {
    $this->actingAs(financeUser());
    $data = financeData();
    $this->post(route('finance.store', 'income'), $data)->assertSessionHasNoErrors();
    $income = FinanceEntry::first();
    $this->post(route('finance.store', 'income'), $data)->assertSessionHasErrors('finance');
    expect(FinanceEntry::count())->toBe(1);
    $this->post(route('finance.store', 'expense'), financeData(['category' => 'Darurat', 'amount' => 100000, 'occurred_on' => '2026-10-19', 'receipt' => UploadedFile::fake()->create('proof.pdf', 5, 'application/pdf')]))->assertSessionHasErrors('finance');
    expect(Storage::disk('local')->allFiles())->toBe([]);
    $this->post(route('finance.store', 'expense'), financeData(['category' => 'Darurat', 'amount' => 100000]))->assertSessionHasNoErrors();
    expect(Ledger::balance())->toBe(900000);
    $this->post(route('finance.void', $income), ['reason' => 'Pembatalan sumber dana'])->assertSessionHasErrors('finance');
    expect($income->fresh()->voided_at)->toBeNull();
    $expense = FinanceEntry::where('kind', 'expense')->first();
    $this->post(route('finance.void', $expense), ['reason' => 'Tidak jadi dibelanjakan'])->assertSessionHasNoErrors();
    expect(Ledger::balance())->toBe(1000000);
    expect($expense->fresh()->voided_at)->not->toBeNull();
    $this->assertDatabaseHas('finance_audits', ['action' => 'pembatalan']);
});
it('validates financial amounts and prevents manual iuran duplication', function () {
    $this->actingAs(financeUser())->post(route('finance.store', 'income'), financeData(['category' => 'Iuran', 'amount' => 0]))->assertSessionHasErrors(['category', 'amount']);
    $this->post(route('finance.store', 'income'), financeData(['occurred_on' => '2026-10-23']))->assertSessionHasErrors('occurred_on');
    expect(FinanceEntry::count())->toBe(0);
});
it('requires chair approval then disburses once and handles partial returns', function () {
    $treasurer = financeUser();
    $chair = financeUser('ketua_umum');
    $s = financeBudget();
    financeCash();
    $this->actingAs($treasurer)->get(route('finance.funding.show', $s))->assertOk();
    $this->post(route('finance.funding.pay', $s), financePayment($s))->assertSessionHasErrors('finance');
    $this->post(route('department.review.save', $s), ['version' => $s->version, 'action' => 'approve', 'note' => 'Setuju'])->assertForbidden();
    $this->actingAs($chair)->get(route('department.review', $s))->assertOk();
    $this->post(route('department.review.save', $s), ['version' => $s->version, 'action' => 'approve', 'note' => 'Anggaran sesuai'])->assertSessionHasNoErrors();
    expect($s->fresh()->status)->toBe('ready_funding');
    expect(Ledger::balance())->toBe(1000000);
    $this->actingAs($treasurer)->post(route('finance.funding.pay', $s), financePayment($s))->assertSessionHasNoErrors();
    expect(Ledger::balance())->toBe(800000);
    expect($s->fresh()->status)->toBe('disbursed');
    $this->post(route('finance.funding.pay', $s), financePayment($s))->assertSessionHasErrors('finance');
    $data = financePayment($s, ['reference' => (string) Str::uuid(), 'amount' => 50000]);
    $this->post(route('finance.funding.refund', $s), $data)->assertSessionHasNoErrors();
    expect(Ledger::balance())->toBe(850000);
    $data['version'] = $s->fresh()->version;
    $this->post(route('finance.funding.refund', $s), $data)->assertSessionHasErrors('finance');
    $this->post(route('finance.funding.refund', $s), financePayment($s, ['reference' => (string) Str::uuid(), 'amount' => 150001]))->assertSessionHasErrors('finance');
    $expense = FinanceEntry::where('origin', 'disbursement')->first();
    $this->post(route('finance.void', $expense), ['reason' => 'Hapus'])->assertSessionHasErrors('finance');
    expect(Storage::disk('local')->allFiles())->toHaveCount(2);
    $this->get(route('finance.monitoring'))->assertOk()->assertSee('150.000')->assertSee('50.000');
    $this->actingAs($s->program->pic)->get(route('department.submissions', [$s->program->department, 'budget', 'tab' => 'expenses', 'program' => $s->program_id]))->assertOk()->assertSee('150.000');
});
it('rolls back an unfunded disbursement including its private proof', function () {
    $s = financeBudget(['status' => 'ready_funding', 'finance_approved_by' => financeUser('ketua_umum')->id, 'finance_approved_at' => now()]);
    $this->actingAs(financeUser())->post(route('finance.funding.pay', $s), financePayment($s))->assertSessionHasErrors('finance');
    expect($s->fresh()->status)->toBe('ready_funding');
    expect(FinanceEntry::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});
it('requires legacy budget approval to be reconfirmed by the chair', function () {
    $s = financeBudget(['status' => 'approved']);
    $this->actingAs(financeUser('ketua_umum'))->get(route('department.review', $s))->assertOk()->assertSee('Setujui pengajuan ini');
    $this->post(route('department.review.save', $s), ['version' => $s->version, 'action' => 'approve', 'note' => 'Diperiksa ulang'])->assertSessionHasNoErrors();
    expect($s->fresh()->status)->toBe('ready_funding');
});
it('connects member and KBH dues to one verified ledger entry', function ($role, $prefix) {
    $member = financeUser($role);
    $treasurer = financeUser();
    $this->actingAs($member)->get(route($prefix.'.index', ['tahun' => 2026]))->assertOk();
    $this->get(route($prefix.'.bayar', ['bulan' => 10, 'tahun' => 2026]))->assertOk();
    $this->post(route($prefix.'.store', 10), ['tahun' => 2026, 'konfirmasi' => 1, 'bukti_transfer' => UploadedFile::fake()->create('transfer.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    $pay = KbhIuranPembayaran::first();
    expect(FinanceEntry::count())->toBe(0);
    $this->get(route('iuran.bukti.unduh', $pay))->assertOk();
    $this->actingAs(financeUser($role))->get(route('iuran.bukti.unduh', $pay))->assertForbidden();
    $this->actingAs($treasurer)->post(route('bendahara.iuran.putuskan', $pay), ['keputusan' => 'lunas'])->assertSessionHasNoErrors();
    expect(Ledger::balance())->toBe(5000);
    $this->post(route('bendahara.iuran.putuskan', $pay), ['keputusan' => 'lunas'])->assertSessionHasErrors('keputusan');
    $this->artisan('himsi:sync-iuran-keuangan')->assertSuccessful();
    $this->artisan('himsi:sync-iuran-keuangan')->assertSuccessful();
    expect(FinanceEntry::where('dues_id', $pay->id)->count())->toBe(1);
    $this->assertDatabaseHas('notifikasi_himsis', ['user_id' => $member->id, 'judul' => 'Iuran Diverifikasi Lunas']);
})->with([['kbh', 'kbh.iuran'], ['anggota_departemen', 'member.dues']]);
it('rejects invalid dues and allows owner resubmission without crediting cash', function () {
    $member = financeUser('anggota_departemen');
    $treasurer = financeUser();
    $this->actingAs($member)->post(route('member.dues.store', 10), ['tahun' => 2026, 'konfirmasi' => 1, 'bukti_transfer' => UploadedFile::fake()->create('transfer.pdf', 10, 'application/pdf')]);
    $pay = KbhIuranPembayaran::first();
    $this->actingAs($treasurer)->post(route('bendahara.iuran.putuskan', $pay), ['keputusan' => 'ditolak'])->assertSessionHasErrors('catatan');
    $this->post(route('bendahara.iuran.putuskan', $pay), ['keputusan' => 'ditolak', 'catatan' => 'Bukti tidak sesuai'])->assertSessionHasNoErrors();
    expect(FinanceEntry::count())->toBe(0);
    $this->actingAs($member)->post(route('member.dues.store', 10), ['tahun' => 2026, 'konfirmasi' => 1, 'bukti_transfer' => UploadedFile::fake()->create('baru.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    expect($pay->fresh()->status)->toBe('menunggu_verifikasi');
});
it('sends a real reminder once per day and routes it to the right owner', function () {
    $member = financeUser('anggota_departemen');
    $this->actingAs(financeUser())->post(route('finance.remind', $member), ['year' => 2026, 'month' => 10])->assertSessionHasNoErrors();
    $this->post(route('finance.remind', $member), ['year' => 2026, 'month' => 10])->assertSessionHasErrors('finance');
    $notice = NotifikasiHimsi::where('user_id', $member->id)->first();
    expect($notice->url)->toContain('/anggota/iuran');
    $this->actingAs($member)->get(route('department.member.notifications', 'kaderisasi'))->assertOk()->assertSee('Pengingat Iuran');
});
it('exposes reports only to authorized observers', function ($role) {
    $this->actingAs(financeUser($role))->get(route('finance.shared'))->assertOk();
    $this->post(route('finance.store', 'income'), financeData())->assertForbidden();
})->with(['ketua_umum', 'koordinator_pengawas', 'super_admin']);
it('does not expose private finance reports to members', function () {
    $this->actingAs(financeUser('kbh'))->get(route('finance.shared'))->assertForbidden();
    $this->get(route('finance.export', 'pdf'))->assertForbidden();
    $this->actingAs(financeUser('super_admin'))->post(route('member.dues.store', 1), [])->assertForbidden();
});
it('includes opening balances and only elapsed fiscal months', function () {
    financeCash();
    DB::transaction(fn () => Ledger::record(['reference' => 'old', 'occurred_on' => '2026-04-01', 'amount' => 500, 'category' => 'Saldo Awal', 'method' => 'Tunai', 'description' => 'Sebelum periode', 'kind' => 'income', 'origin' => 'manual']));
    $summary = Ledger::summary();
    expect($summary['opening'])->toBe(500);
    expect($summary['balance'])->toBe(1000500);
    $this->actingAs(financeUser())->get(route('finance.dashboard'))->assertOk()->assertViewHas('months', fn ($months) => count($months) === 6);
});
it('exports filtered PDF and real XLSX with safe text cells', function ($format) {
    financeCash();
    DB::transaction(fn () => Ledger::record(['reference' => 'formula', 'occurred_on' => '2026-10-01', 'amount' => 42, 'category' => 'Donasi', 'method' => 'Tunai', 'description' => '=HYPERLINK("https://example.test")', 'kind' => 'income', 'origin' => 'manual']));
    $r = $this->actingAs(financeUser())->get(route('finance.export', ['format' => $format, 'month' => '2026-10']))->assertOk();
    if ($format === 'pdf') {
        expect(substr($r->getContent(), 0, 5))->toBe('%PDF-');
    } else {
        $path = tempnam(sys_get_temp_dir(), 'test-export-').'.zip';
        file_put_contents($path, $r->getContent());
        $zip = new PharData($path);
        $xml = $zip['xl/worksheets/sheet1.xml']->getContent();
        expect($xml)->toContain('inlineStr')->toContain('HYPERLINK')->not->toContain('<f>')->not->toContain('Dana awal');
        unset($zip);
        unlink($path);
    }
})->with(['pdf', 'xlsx']);

it('exports all six departments with disbursements separate from allocations', function () {
    $chair = financeUser('ketua_umum');
    financeBudget(['status' => 'ready_funding', 'finance_approved_by' => $chair->id, 'finance_approved_at' => now()]);
    $response = $this->actingAs(financeUser())->get(route('finance.export', ['format' => 'xlsx', 'scope' => 'monitoring']))->assertOk();
    $path = tempnam(sys_get_temp_dir(), 'monitor-export-').'.zip';
    file_put_contents($path, $response->getContent());
    $zip = new PharData($path);
    $xml = $zip['xl/worksheets/sheet1.xml']->getContent();
    foreach (config('superadmin.departments') as $name) {
        expect($xml)->toContain($name);
    }
    expect($xml)->toContain('<v>200000</v>')->toContain('Dicairkan (Rp)');
    unset($zip);
    unlink($path);
});
