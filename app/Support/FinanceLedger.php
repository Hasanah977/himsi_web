<?php

namespace App\Support;

use App\Models\FinanceEntry;
use App\Models\KbhIuranPembayaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceLedger
{
    public static function lock(): void
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Cash writes require a transaction.');
        }
        DB::table('finance_cash_locks')->where('id', 1)->lockForUpdate()->firstOrFail();
    }

    public static function query(): Builder
    {
        return FinanceEntry::whereNull('voided_at');
    }

    public static function balance(): int
    {
        return (int) self::query()->selectRaw("COALESCE(SUM(CASE WHEN kind='income' THEN amount ELSE -CAST(amount AS SIGNED) END),0) AS balance")->value('balance');
    }

    public static function ensure(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['finance' => $message]);
        }
    }

    public static function solvent(): void
    {
        $balance = 0;
        foreach (self::query()->orderBy('occurred_on')->orderBy('id')->cursor() as $entry) {
            $balance += $entry->kind === 'income' ? $entry->amount : -$entry->amount;
            self::ensure($balance >= 0, 'Saldo kas tidak mencukupi pada tanggal transaksi. Periksa pemasukan dan tanggal pencatatan.');
        }
    }

    public static function record(array $data): FinanceEntry
    {
        self::lock();
        $entry = FinanceEntry::create($data + ['actor_id' => auth()->id()]);
        self::solvent();
        self::audit($entry->kind === 'income' ? 'pemasukan' : 'pengeluaran', 'Catat '.$entry->category.' — '.$entry->description.' sebesar Rp'.number_format($entry->amount, 0, ',', '.').' ('.$entry->reference.').');

        return $entry;
    }

    public static function audit(string $action, string $description): void
    {
        DB::table('finance_audits')->insert(['actor_id' => auth()->id(), 'action' => $action, 'description' => $description, 'created_at' => now()]);
    }

    public static function dues(KbhIuranPembayaran $payment): void
    {
        self::lock();
        if ($payment->status !== 'lunas' || FinanceEntry::where('dues_id', $payment->id)->exists()) {
            return;
        }
        self::record(['reference' => 'iuran:'.$payment->id, 'kind' => 'income', 'origin' => 'dues', 'occurred_on' => ($payment->diverifikasi_pada ?? $payment->updated_at)->toDateString(), 'amount' => $payment->nominal, 'category' => 'Iuran', 'description' => 'Iuran '.$payment->bulan.'/'.$payment->tahun.' — '.$payment->anggota->name, 'method' => 'Transfer', 'dues_id' => $payment->id, 'receipt_path' => $payment->bukti_path, 'receipt_name' => $payment->bukti_nama]);
    }

    public static function duesUrl(User $user, int $year): string
    {
        return $user->role === 'anggota_departemen' ? route('member.dues.index', ['tahun' => $year], false) : route('kbh.iuran.index', ['tahun' => $year], false);
    }

    public static function summary(?string $start = null, ?string $end = null): array
    {
        $start ??= config('finance.start');
        $end ??= config('finance.end');
        $before = self::query()->where('occurred_on', '<', $start);
        $opening = (int) (clone $before)->where('kind', 'income')->sum('amount') - (int) (clone $before)->where('kind', 'expense')->sum('amount');
        $entries = self::query()->whereBetween('occurred_on', [$start, $end]);
        $income = (int) (clone $entries)->where('kind', 'income')->sum('amount');
        $expense = (int) (clone $entries)->where('kind', 'expense')->sum('amount');

        return ['opening' => $opening, 'income' => $income, 'expense' => $expense, 'balance' => $opening + $income - $expense, 'ratio' => $income ? round($expense / $income * 100, 1) : 0];
    }
}
