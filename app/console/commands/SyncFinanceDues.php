<?php

namespace App\Console\Commands;

use App\Models\KbhIuranPembayaran;
use App\Support\FinanceLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncFinanceDues extends Command
{
    protected $signature = 'himsi:sync-iuran-keuangan';

    protected $description = 'Catat iuran lama yang telah lunas ke buku kas tanpa duplikasi';

    public function handle(): int
    {
        KbhIuranPembayaran::where('status', 'lunas')->chunkById(100, function ($items) {
            foreach ($items as $item) {
                DB::transaction(function () use ($item) {
                    FinanceLedger::lock();
                    FinanceLedger::dues(KbhIuranPembayaran::whereKey($item->id)->lockForUpdate()->firstOrFail());
                }, 3);
            }
        });
        $this->info('Iuran lunas berhasil disinkronkan tanpa pencatatan ganda.');

        return self::SUCCESS;
    }
}
