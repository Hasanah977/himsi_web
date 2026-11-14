<?php

use App\Models\FinanceEntry;
use App\Models\HimsiRegistration;
use App\Models\KbhIuranPembayaran;
use App\Models\NotifikasiHimsi;
use App\Models\OpsSubmission;
use App\Models\User;
use App\Support\FinanceLedger;
use App\Support\FinanceReport;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(now()->setDate(2026, 10, 24)->setTime(12, 0));
    Storage::fake('local');
});

it('sends bulk reminders only to eligible unpaid members without duplicates', function () {
    $treasurer = User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]);
    $unpaid = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $paid = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $waiting = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    $inactive = User::factory()->create(['role' => 'kbh', 'is_active' => false]);
    $applicant = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    HimsiRegistration::factory()->create(['user_id' => $applicant->id, 'status' => 'pending']);
    foreach ([$paid->id => 'lunas', $waiting->id => 'menunggu_verifikasi'] as $id => $status) {
        KbhIuranPembayaran::create(['user_id' => $id, 'tahun' => 2026, 'bulan' => 10, 'nominal' => 5000, 'bukti_path' => 'proof.pdf', 'bukti_nama' => 'proof.pdf', 'bukti_mime' => 'application/pdf', 'status' => $status]);
    }
    $this->actingAs($treasurer)->post(route('finance.remind.all'), ['year' => 2026, 'month' => 10])->assertSessionHasNoErrors();
    $this->post(route('finance.remind.all'), ['year' => 2026, 'month' => 10])->assertSessionHasNoErrors();
    expect(NotifikasiHimsi::where('judul', 'Pengingat Iuran 10/2026')->pluck('user_id')->all())->toBe([$unpaid->id]);
    $this->actingAs($unpaid)->post(route('finance.remind.all'), ['year' => 2026, 'month' => 10])->assertForbidden();
});

it('filters the combined billing screen by payment status and member NIM', function () {
    $member = User::factory()->create(['role' => 'kbh', 'is_active' => true]);
    HimsiRegistration::factory()->create(['user_id' => $member->id, 'status' => 'approved', 'nim' => 'D0424001']);
    KbhIuranPembayaran::create(['user_id' => $member->id, 'tahun' => 2026, 'bulan' => 10, 'nominal' => 5000, 'bukti_path' => 'proof.pdf', 'bukti_nama' => 'proof.pdf', 'bukti_mime' => 'application/pdf', 'status' => 'menunggu_verifikasi']);
    $this->actingAs(User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]))
        ->get(route('finance.billing', ['q' => 'D0424001', 'status' => 'menunggu_verifikasi']))->assertOk()
        ->assertViewHas('users', fn ($users) => $users->getCollection()->modelKeys() === [$member->id]);
    $this->get(route('finance.billing', ['status' => 'belum']))->assertOk()->assertViewHas('users', fn ($users) => $users->isEmpty());
});

it('requires treasury review before chair approval for incoming requests', function () {
    $treasurer = User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]);
    $chair = User::factory()->create(['role' => 'ketua_umum', 'is_active' => true]);
    $submission = OpsSubmission::factory()->create(['type' => 'budget', 'status' => 'pending_treasurer', 'version' => 1, 'total' => 100000]);
    $this->actingAs($chair)->post(route('department.review.save', $submission), ['version' => 1, 'action' => 'approve', 'note' => 'Approve'])->assertForbidden();
    $this->actingAs($treasurer)->get(route('finance.funding', ['tab' => 'incoming']))->assertOk()->assertSee($submission->program->title);
    $this->post(route('department.review.save', $submission), ['version' => 1, 'action' => 'forward', 'note' => 'RAB lengkap'])->assertSessionHasNoErrors();
    expect($submission->fresh()->status)->toBe('pending_chair');
    expect($submission->fresh()->finance_approved_by)->toBeNull();
    $this->actingAs($chair)->post(route('department.review.save', $submission), ['version' => 2, 'action' => 'approve', 'note' => 'Disetujui'])->assertSessionHasNoErrors();
    expect($submission->fresh()->status)->toBe('ready_funding');
});

it('balances the trial balance with prior advances and current refunds', function () {
    $entries = [
        ['opening', '2026-05-01', 'income', 'manual', 1000000],
        ['advance', '2026-09-01', 'expense', 'disbursement', 200000],
        ['refund', '2026-10-01', 'income', 'return', 50000],
        ['expense', '2026-10-02', 'expense', 'manual', 100000],
    ];
    foreach ($entries as [$reference, $date, $kind, $origin, $amount]) {
        DB::transaction(fn () => FinanceLedger::record(['reference' => $reference, 'occurred_on' => $date, 'kind' => $kind, 'origin' => $origin, 'amount' => $amount, 'category' => 'Lainnya', 'description' => $reference, 'method' => 'Transfer']));
    }
    $data = FinanceReport::data('2026-10-01', '2026-10-31');
    expect($data['advance'])->toBe(150000)->and($data['expense'])->toBe(100000);
    expect(array_sum(array_column($data['accounts'], 'debit')))->toBe(array_sum(array_column($data['accounts'], 'credit')));
    $this->actingAs(User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]))->get(route('finance.report', ['month' => '2026-10']))->assertOk()->assertSee('Neraca Saldo HIMSI')->assertSee('Uang Muka Program Kerja');
});

it('previews private receipts inline and searches activity descriptions', function () {
    Storage::disk('local')->put('finance/receipts/example.pdf', '%PDF-1.4');
    $entry = FinanceEntry::create(['reference' => 'example', 'occurred_on' => '2026-10-01', 'amount' => 10000, 'kind' => 'income', 'origin' => 'manual', 'category' => 'Donasi', 'description' => 'Donasi alumni', 'method' => 'Transfer', 'receipt_path' => 'finance/receipts/example.pdf', 'receipt_name' => 'example.pdf']);
    FinanceLedger::audit('pemasukan', 'Donasi alumni diterima');
    FinanceLedger::audit('pengeluaran', 'Pembelian alat tulis');
    $this->actingAs(User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]));
    $response = $this->get(route('finance.receipt', $entry))->assertOk();
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline;');
    $this->get(route('finance.logs', ['q' => 'alumni']))->assertOk()->assertSee('Donasi alumni diterima')->assertDontSee('Pembelian alat tulis');
    $this->actingAs(User::factory()->create(['role' => 'kbh', 'is_active' => true]))->get(route('finance.receipt', $entry))->assertForbidden();
});

it('renders multipage table PDFs including long text and monitoring totals', function () {
    for ($i = 0; $i < 28; $i++) {
        FinanceEntry::create(['reference' => 'sample-'.$i, 'occurred_on' => '2026-10-01', 'amount' => 50000, 'kind' => 'income', 'origin' => 'manual', 'category' => 'Donasi', 'description' => str_repeat('Donasi alumni untuk kegiatan mahasiswa. ', $i === 0 ? 45 : 2), 'method' => 'Transfer']);
    }
    $this->actingAs(User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]));
    foreach (['income', 'monitoring'] as $scope) {
        $response = $this->get(route('finance.export', ['format' => 'pdf'] + ($scope === 'income' ? ['kind' => 'income'] : ['scope' => 'monitoring'])))->assertOk();
        expect($response->getContent())->toStartWith('%PDF-');
        if ($scope === 'income') {
            expect(substr_count($response->getContent(), '/Type /Page'.chr(10)))->toBeGreaterThan(1);
        }
        if (getenv('FINANCE_PDF_PREVIEW') === '1') {
            file_put_contents(storage_path('app/finance-preview-'.$scope.'.pdf'), $response->getContent());
        }
    }
});

it('paginates billing with compact Bootstrap controls and preserves filters', function () {
    User::factory()->count(21)->create(['role' => 'kbh', 'is_active' => true]);
    $treasurer = User::factory()->create(['role' => 'bendahara_umum', 'is_active' => true]);
    $response = $this->actingAs($treasurer)->get(route('finance.billing', ['year' => 2026, 'month' => 10, 'status' => 'belum']))->assertOk();
    $response->assertSee('class="pagination"', false)->assertDontSee('<svg', false);
    $nextPage = $response->viewData('users')->nextPageUrl();
    expect($nextPage)->toContain('page=2')->toContain('status=belum');
    $this->get($nextPage)->assertOk()->assertViewHas('users', fn ($users) => $users->currentPage() === 2 && $users->count() === 1);
});
