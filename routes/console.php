<?php

use App\Support\Governance;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('himsi:close-expired-periods', function (): void {
    Governance::expire();
    $this->info('Periode kedaluwarsa telah ditutup.');
});
Schedule::command('himsi:close-expired-periods')->everyMinute()->withoutOverlapping();
