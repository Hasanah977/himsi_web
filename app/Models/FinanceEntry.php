<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Illuminate\Database\Eloquent\Model;

class FinanceEntry extends Model
{
    use ProtectsPastPeriods;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['occurred_on' => 'date', 'amount' => 'integer', 'voided_at' => 'datetime'];
    }
}
