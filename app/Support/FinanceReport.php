<?php

namespace App\Support;

use App\Models\OpsSubmission;

class FinanceReport
{
    public static function data(string $start, string $end): array
    {
        $summary = FinanceLedger::summary($start, $end);
        $period = FinanceLedger::query()->whereBetween('occurred_on', [$start, $end])->get();
        $before = FinanceLedger::query()->where('occurred_on', '<', $start)->get();
        $openingAdvance = $before->where('origin', 'disbursement')->sum('amount') - $before->where('origin', 'return')->sum('amount');
        $paid = (int) $period->where('origin', 'disbursement')->sum('amount');
        $returned = (int) $period->where('origin', 'return')->sum('amount');
        $expense = $summary['expense'] - $paid;
        $advance = $openingAdvance + $paid - $returned;
        $accounts = [];
        foreach ([['111', 'Kas HIMSI', $summary['balance']], ['113', 'Uang Muka Program Kerja', $advance], ['311', 'Saldo Awal Dana', -($summary['opening'] + $openingAdvance)], ['411', 'Penerimaan Dana', -($summary['income'] - $returned)], ['511', 'Beban Operasional', $expense]] as [$code, $name, $balance]) {
            $accounts[] = ['code' => $code, 'name' => $name, 'debit' => max(0, $balance), 'credit' => max(0, -$balance)];
        }
        $departments = [];
        foreach (config('superadmin.departments') as $key => $name) {
            $rows = $period->where('department', $key);
            $allocated = (int) OpsSubmission::where('type', 'budget')->whereBetween('finance_approved_at', [$start.' 00:00:00', $end.' 23:59:59'])->whereHas('program', fn ($q) => $q->where('department', $key))->sum('total');
            $outgoing = (int) $rows->where('kind', 'expense')->sum('amount');
            $refund = (int) $rows->where('origin', 'return')->sum('amount');
            $departments[] = ['name' => $name, 'allocated' => $allocated, 'paid' => $outgoing, 'returned' => $refund, 'used' => $outgoing - $refund, 'remaining' => $allocated - $outgoing + $refund];
        }
        $programs = [];
        $submissions = OpsSubmission::with('program')->whereIn('id', $period->pluck('submission_id')->filter()->unique())->get();
        foreach ($submissions as $submission) {
            $rows = $period->where('submission_id', $submission->id);
            $outgoing = (int) $rows->where('origin', 'disbursement')->sum('amount');
            $refund = (int) $rows->where('origin', 'return')->sum('amount');
            $programs[] = ['name' => $submission->program?->title ?? 'Program dihapus', 'department' => config('superadmin.departments.'.$submission->program?->department), 'requested' => $submission->total, 'paid' => $outgoing, 'returned' => $refund, 'net' => $outgoing - $refund];
        }

        return compact('accounts', 'paid', 'returned', 'expense', 'advance', 'departments', 'programs');
    }
}
