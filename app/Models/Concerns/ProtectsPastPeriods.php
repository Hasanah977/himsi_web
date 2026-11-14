<?php

namespace App\Models\Concerns;

use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\SuratKeluar;
use App\Support\Governance;
use App\Support\PeriodBuilder;

trait ProtectsPastPeriods
{
    public function newEloquentBuilder($query): PeriodBuilder
    {
        return new PeriodBuilder($query);
    }

    public static function bootProtectsPastPeriods(): void
    {
        static::saving(function ($model): void {
            Governance::assertWritable($model);
            foreach (['program_id' => OpsProgram::class, 'ops_submission_id' => OpsSubmission::class, 'submission_id' => OpsSubmission::class, 'surat_keluar_id' => SuratKeluar::class] as $key => $class) {
                if ($model->getAttribute($key)) {
                    $parent = $class::find($model->getAttribute($key));
                    if ($parent) {
                        Governance::assertWritable($parent);
                    }
                }
            }
        });
        static::deleting(function ($model): void {
            Governance::assertWritable($model);
        });
    }
}
