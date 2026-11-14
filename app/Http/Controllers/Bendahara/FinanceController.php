<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\FinanceEntry;
use App\Models\KbhIuranPembayaran;
use App\Models\NotifikasiHimsi;
use App\Models\OpsSubmission;
use App\Models\User;
use App\Support\FinanceLedger as Ledger;
use App\Support\FinanceReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function dashboard(): View
    {
        $summary = Ledger::summary();
        $months = [];
        $last = min(now()->toDateString(), config('finance.end'));
        for ($d = Carbon::parse(config('finance.start'))->startOfMonth(); $d->toDateString() <= $last; $d->addMonth()) {
            $month = Ledger::summary(max($d->toDateString(), config('finance.start')), min($d->copy()->endOfMonth()->toDateString(), $last));
            $months[] = ['label' => $d->translatedFormat('M Y')] + $month;
        }

        $dues = $this->duesSummary(now()->year, now()->month);

        return view('bendahara.finance.dashboard', [
            'dues' => $dues,
            'incoming' => OpsSubmission::where('type', 'budget')->where('status', 'pending_treasurer')->count(),
            'activeFunding' => OpsSubmission::where('type', 'budget')->whereIn('status', ['pending_treasurer', 'pending_chair', 'ready_funding'])->count(),
            'summary' => $summary, 'months' => array_slice($months, -6),
            'recentFunding' => OpsSubmission::with('program')->where('type', 'budget')->where('status', '!=', 'draft')->latest()->limit(5)->get(),
            'entries' => Ledger::query()->latest('occurred_on')->latest('id')->limit(5)->get(),
            'categories' => Ledger::query()->whereBetween('occurred_on', [config('finance.start'), config('finance.end')])->where('kind', 'income')->selectRaw('category,SUM(amount) AS total')->groupBy('category')->pluck('total', 'category'),
            'pending' => KbhIuranPembayaran::where('status', 'menunggu_verifikasi')->count(),
            'ready' => OpsSubmission::where('type', 'budget')->where('status', 'ready_funding')->count(),
            'unsynced' => KbhIuranPembayaran::where('status', 'lunas')->whereNotIn('id', FinanceEntry::whereNotNull('dues_id')->select('dues_id'))->count(),
        ]);
    }

    public function entries(Request $request, string $kind): View
    {
        abort_unless(in_array($kind, ['income', 'expense']), 404);
        $query = $this->filtered($request)->where('kind', $kind);

        return view('bendahara.finance.entries', ['kind' => $kind, 'entries' => $query->latest('occurred_on')->latest('id')->paginate(15)->withQueryString(), 'summary' => Ledger::summary()]);
    }

    public function filtered(Request $request): Builder
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m'], 'q' => ['nullable', 'string', 'max:120']]);
        $query = Ledger::query()->whereBetween('occurred_on', [config('finance.start'), config('finance.end')]);
        if ($request->filled('month')) {
            $d = Carbon::createFromFormat('!Y-m', $request->input('month'));
            $query->whereBetween('occurred_on', [$d->toDateString(), $d->copy()->endOfMonth()->toDateString()]);
        }
        if ($request->filled('q')) {
            $query->where('description', 'like', '%'.$request->input('q').'%');
        }

        return $query;
    }

    public function store(Request $request, string $kind): RedirectResponse
    {
        abort_unless(in_array($kind, ['income', 'expense']), 404);
        $data = $request->validate([
            'reference' => ['required', 'uuid'], 'occurred_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.config('finance.start'), 'before_or_equal:'.min(now()->toDateString(), config('finance.end'))],
            'amount' => ['required', 'integer', 'between:1,1000000000000'], 'category' => ['required', Rule::in(config('finance.categories.'.$kind))],
            'description' => ['required', 'string', 'max:2000'], 'method' => ['required', Rule::in(['Transfer', 'Tunai'])],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $path = null;
        try {
            DB::transaction(function () use ($data, $request, $kind, &$path) {
                Ledger::lock();
                $ref = 'manual:'.$data['reference'];
                Ledger::ensure(! FinanceEntry::where('reference', $ref)->exists(), 'Transaksi ini sudah disimpan. Muat ulang halaman.');
                if ($request->hasFile('receipt')) {
                    $path ??= $request->file('receipt')->store('finance/receipts', 'local');
                    Ledger::ensure((bool) $path, 'Bukti gagal disimpan.');
                }
                Ledger::record(collect($data)->except(['reference', 'receipt'])->all() + ['reference' => $ref, 'kind' => $kind, 'origin' => 'manual', 'receipt_path' => $path, 'receipt_name' => $request->file('receipt')?->getClientOriginalName()]);
            }, 3);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return back()->with('success', 'Transaksi berhasil dicatat ke kas HIMSI.');
    }

    public function void(Request $request, FinanceEntry $entry): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($entry, $data) {
            Ledger::lock();
            $e = FinanceEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            Ledger::ensure($e->origin === 'manual' && ! $e->voided_at, 'Hanya transaksi manual aktif yang dapat dibatalkan.');
            $e->update(['voided_at' => now(), 'void_reason' => $data['reason']]);
            Ledger::solvent();
            Ledger::audit('pembatalan', 'Batalkan '.$e->reference.': '.$data['reason']);
        }, 3);

        return back()->with('success', 'Transaksi dibatalkan. Riwayat tetap disimpan untuk audit.');
    }

    public function receipt(Request $request, FinanceEntry $entry): StreamedResponse
    {
        abort_unless(in_array($request->user()->role, ['bendahara_umum', 'super_admin', 'ketua_umum', 'koordinator_pengawas'])
            || ($request->user()->role === 'koordinator_departemen' && $entry->department === $request->user()->department && in_array($entry->origin, ['return', 'disbursement'], true)), 403);

        abort_unless($entry->receipt_path && Storage::disk('local')->exists($entry->receipt_path), 404);
        if ($request->boolean('download')) {
            return Storage::disk('local')->download($entry->receipt_path, $entry->receipt_name);
        }

        return Storage::disk('local')->response($entry->receipt_path, $entry->receipt_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function report(Request $request): View
    {
        abort_unless(in_array($request->user()->role, ['bendahara_umum', 'super_admin', 'ketua_umum', 'koordinator_pengawas']), 403);
        $query = $this->filtered($request);
        $start = config('finance.start');
        $end = config('finance.end');
        if ($request->filled('month')) {
            $d = Carbon::createFromFormat('!Y-m', $request->input('month'));
            $start = max($start, $d->toDateString());
            $end = min($end, $d->endOfMonth()->toDateString());
        }

        return view('bendahara.finance.report', ['summary' => Ledger::summary($start, $end), 'entries' => $query->orderByDesc('occurred_on')->orderByDesc('id')->paginate(20)->withQueryString(), 'start' => $start, 'end' => $end, 'report' => FinanceReport::data($start, $end)]);
    }

    public function monitoring(): View
    {
        return view('bendahara.finance.monitoring', ['rows' => $this->monitorRows()]);
    }

    public function monitorRows(): array
    {
        return FinanceReport::data(config('finance.start'), config('finance.end'))['departments'];
    }

    public function logs(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120'], 'action' => ['nullable', 'string', 'max:80']]);
        $base = DB::table('finance_audits');
        $stats = ['total' => (clone $base)->count(), 'today' => (clone $base)->whereDate('created_at', today())->count()];
        $actions = (clone $base)->distinct()->pluck('action');
        $query = $base->leftJoin('users', 'users.id', '=', 'finance_audits.actor_id')->select('finance_audits.*', 'users.name');
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request): void {
                $term = '%'.$request->input('q').'%';
                $q->where('description', 'like', $term)->orWhere('action', 'like', $term)->orWhere('users.name', 'like', $term);
            });
        }
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }
        $entries = $query->orderByDesc('finance_audits.id')->paginate(20)->withQueryString();
        $latestIncome = Ledger::query()->where('kind', 'income')->latest('id')->first();
        $latestExpense = Ledger::query()->where('kind', 'expense')->latest('id')->first();

        return view('bendahara.finance.logs', compact('entries', 'stats', 'actions', 'latestIncome', 'latestExpense'));
    }

    public function duesMembers(): Builder
    {
        return User::where('is_active', true)->where(function (Builder $query): void {
            $query->whereIn('role', config('finance.dues_roles'))
                ->orWhere(function (Builder $query): void {
                    $query->whereIn('role', ['ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_departemen'])
                        ->whereHas('registration', fn ($query) => $query->where('status', 'approved'));
                });
        })
            ->whereDoesntHave('registration', fn ($q) => $q->where('status', '!=', 'approved'));
    }

    public function duesSummary(int $year, int $month): array
    {
        $payments = KbhIuranPembayaran::whereIn('user_id', $this->duesMembers()->select('id'))->where('tahun', $year)->where('bulan', $month);
        $total = $this->duesMembers()->count();
        $paid = (clone $payments)->where('status', 'lunas')->count();
        $pending = (clone $payments)->where('status', 'menunggu_verifikasi')->count();

        return ['total' => $total, 'paid' => $paid, 'pending' => $pending, 'unpaid' => $total - $paid - $pending, 'collected' => (int) (clone $payments)->where('status', 'lunas')->sum('nominal'), 'progress' => $total ? round($paid / $total * 100, 1) : 0];
    }

    public function billing(Request $request): View
    {
        $data = $request->validate(['year' => ['nullable', 'integer', 'between:2026,2100'], 'month' => ['nullable', 'integer', 'between:1,12'], 'q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', 'in:semua,lunas,belum,menunggu_verifikasi,ditolak']]);
        $year = (int) ($data['year'] ?? now()->year);
        $month = (int) ($data['month'] ?? now()->month);
        $status = $data['status'] ?? 'semua';
        $summary = $this->duesSummary($year, $month);
        $query = $this->duesMembers()->with('registration');
        $paymentQuery = KbhIuranPembayaran::where('tahun', $year)->where('bulan', $month);
        if ($status === 'belum') {
            $query->whereNotIn('id', (clone $paymentQuery)->whereIn('status', ['lunas', 'menunggu_verifikasi'])->select('user_id'));
        } elseif ($status !== 'semua') {
            $query->whereIn('id', (clone $paymentQuery)->where('status', $status)->select('user_id'));
        }
        if ($request->filled('q')) {
            $term = '%'.$data['q'].'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhereHas('registration', fn ($r) => $r->where('nim', 'like', $term)));
        }
        $users = $query->orderBy('name')->paginate(20)->withQueryString();
        $payments = $paymentQuery->whereIn('user_id', $users->pluck('id'))->get()->keyBy('user_id');

        return view('bendahara.finance.billing', compact('users', 'payments', 'year', 'month', 'status', 'summary'));
    }

    public function remindAll(Request $request): RedirectResponse
    {
        $data = $request->validate(['year' => ['required', 'integer', 'between:2026,2100'], 'month' => ['required', 'integer', 'between:1,12']]);
        Ledger::ensure(sprintf('%04d-%02d', $data['year'], $data['month']) <= now()->format('Y-m'), 'Periode iuran belum berjalan.');
        $sent = 0;
        $title = 'Pengingat Iuran '.$data['month'].'/'.$data['year'];
        $this->duesMembers()->orderBy('id')->chunkById(100, function ($users) use ($data, $title, &$sent): void {
            foreach ($users as $user) {
                $sent += DB::transaction(function () use ($user, $data, $title): int {
                    User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                    if (KbhIuranPembayaran::where('user_id', $user->id)->where('tahun', $data['year'])->where('bulan', $data['month'])->whereIn('status', ['lunas', 'menunggu_verifikasi'])->exists()
                        || NotifikasiHimsi::where('user_id', $user->id)->where('judul', $title)->whereDate('created_at', today())->exists()) {
                        return 0;
                    }
                    NotifikasiHimsi::kirim($user->id, $title, 'Silakan membayar iuran Rp'.number_format(config('himsi_iuran.nominal_per_bulan'), 0, ',', '.').' dan unggah bukti melalui menu Iuran.', Ledger::duesUrl($user, (int) $data['year']), 'iuran');

                    return 1;
                }, 3);
            }
        });
        Ledger::audit('pengingat', $title.' dikirim massal kepada '.$sent.' anggota yang belum membayar.');

        return back()->with('success', 'Pengingat terkirim kepada '.$sent.' anggota. Akun lunas, menunggu verifikasi, dan yang sudah diingatkan hari ini dilewati.');
    }

    public function remind(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['year' => ['required', 'integer', 'between:2026,2100'], 'month' => ['required', 'integer', 'between:1,12']]);
        abort_unless($this->duesMembers()->whereKey($user->id)->exists(), 404);
        Ledger::ensure(sprintf('%04d-%02d', $data['year'], $data['month']) <= now()->format('Y-m'), 'Periode iuran belum berjalan.');
        DB::transaction(function () use ($user, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            Ledger::ensure(! KbhIuranPembayaran::where('user_id', $user->id)->where('tahun', $data['year'])->where('bulan', $data['month'])->whereIn('status', ['lunas', 'menunggu_verifikasi'])->exists(), 'Iuran sudah lunas atau menunggu verifikasi.');
            $title = 'Pengingat Iuran '.$data['month'].'/'.$data['year'];
            Ledger::ensure(! NotifikasiHimsi::where('user_id', $user->id)->where('judul', $title)->whereDate('created_at', today())->exists(), 'Pengingat untuk periode ini sudah dikirim hari ini.');
            NotifikasiHimsi::kirim($user->id, $title, 'Silakan membayar iuran Rp'.number_format(config('himsi_iuran.nominal_per_bulan'), 0, ',', '.').' dan unggah bukti melalui menu Iuran.', Ledger::duesUrl($user, $data['year']), 'iuran');
            Ledger::audit('pengingat', $title.' untuk '.$user->name);
        }, 3);

        return back()->with('success', 'Pengingat masuk ke notifikasi anggota.');
    }
}
