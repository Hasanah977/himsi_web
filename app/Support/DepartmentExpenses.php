<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepartmentExpenses
{
    public function forProgram(string $department, int $program): array
    {
        $map = config('department.expenses');
        if (! $map) {
            $query = FinanceLedger::query()->where('department', $department)->where('program_id', $program)->whereIn('origin', ['disbursement', 'return']);
            $total = (int) (clone $query)->where('origin', 'disbursement')->sum('amount') - (int) (clone $query)->where('origin', 'return')->sum('amount');
            $rows = $query->selectRaw("occurred_on as date, description, CASE WHEN origin='return' THEN -CAST(amount AS SIGNED) ELSE amount END as amount")->orderByDesc('occurred_on')->paginate(20)->withQueryString();

            return compact('rows', 'total') + ['connected' => true];
        }

        if (! $map) {
            return ['connected' => false, 'rows' => collect(), 'total' => null];
        }
        foreach (['table', 'program', 'department', 'amount', 'date', 'description'] as $key) {
            if (empty($map[$key]) || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $map[$key])) {
                return ['connected' => false, 'rows' => collect(), 'total' => null];
            }
        }
        if (! Schema::hasTable($map['table']) || ! Schema::hasColumns($map['table'], array_map(fn ($key) => $map[$key], ['program', 'department', 'amount', 'date', 'description']))) {
            return ['connected' => false, 'rows' => collect(), 'total' => null];
        }
        $query = DB::table($map['table'])->where($map['department'], $department)->where($map['program'], $program);
        if (isset($map['status_column'],$map['status_value'])) {
            if (! Schema::hasColumn($map['table'], $map['status_column'])) {
                return ['connected' => false, 'rows' => collect(), 'total' => null];
            }
            $query->where($map['status_column'], $map['status_value']);
        }
        $total = (clone $query)->sum($map['amount']);
        $rows = $query->select([$map['date'].' as date', $map['description'].' as description', $map['amount'].' as amount'])->orderByDesc($map['date'])->paginate(20)->withQueryString();

        return compact('rows', 'total') + ['connected' => true];
    }
}
