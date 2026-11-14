<?php

namespace Database\Seeders;

use App\Models\FinanceEntry;
use App\Models\OpsProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $bendahara = User::where('email', 'bendahara@himsi.test')->first();
        $prokerDanus = OpsProgram::where('department', 'dana_usaha')->first();
        $prokerKesekretariatan = OpsProgram::where('department', 'kesekretariatan')->first();

        // 1. Pastikan lock record ada
        DB::table('finance_cash_locks')->insertOrIgnore(['id' => 1]);

        // 2. Transaksi Kas Masuk & Keluar (Finance Entries)
        $entries = [
            [
                'reference' => 'FIN-IN-2026-001',
                'kind' => 'income',
                'origin' => 'manual',
                'occurred_on' => '2026-01-10',
                'amount' => 5000000,
                'category' => 'Saldo Awal',
                'description' => 'Saldo awal kas kepengurusan himpunan dari periode sebelumnya.',
                'method' => 'transfer',
                'department' => null,
                'program_id' => null,
                'actor_id' => $bendahara?->id,
                'receipt_path' => 'finance/bukti_saldo_awal.pdf',
                'receipt_name' => 'bukti_saldo_awal.pdf',
            ],
            [
                'reference' => 'FIN-IN-2026-002',
                'kind' => 'income',
                'origin' => 'program',
                'occurred_on' => '2026-02-15',
                'amount' => 1250000,
                'category' => 'Dana Usaha',
                'description' => 'Keuntungan penjualan merchandise dan lanyard HIMSI gelombang 1.',
                'method' => 'cash',
                'department' => 'dana_usaha',
                'program_id' => $prokerDanus?->id,
                'actor_id' => $bendahara?->id,
                'receipt_path' => 'finance/nota_danus_lanyard.jpg',
                'receipt_name' => 'nota_danus_lanyard.jpg',
            ],
            [
                'reference' => 'FIN-IN-2026-003',
                'kind' => 'income',
                'origin' => 'manual',
                'occurred_on' => '2026-03-01',
                'amount' => 2000000,
                'category' => 'Sponsor',
                'description' => 'Dana sponsorship kegiatan dari Bank Mandiri Mitra Unsulbar.',
                'method' => 'transfer',
                'department' => 'humas',
                'program_id' => null,
                'actor_id' => $bendahara?->id,
                'receipt_path' => 'finance/bukti_sponsor_mandiri.pdf',
                'receipt_name' => 'bukti_sponsor_mandiri.pdf',
            ],
            [
                'reference' => 'FIN-OUT-2026-001',
                'kind' => 'expense',
                'origin' => 'program',
                'occurred_on' => '2026-02-05',
                'amount' => 450000,
                'category' => 'Administrasi',
                'description' => 'Pembelian binder arsip, map dokumen, tinta printer, dan kertas HVS sekretariat.',
                'method' => 'cash',
                'department' => 'kesekretariatan',
                'program_id' => $prokerKesekretariatan?->id,
                'actor_id' => $bendahara?->id,
                'receipt_path' => 'finance/nota_atk_sekretariat.jpg',
                'receipt_name' => 'nota_atk_sekretariat.jpg',
            ],
            [
                'reference' => 'FIN-OUT-2026-002',
                'kind' => 'expense',
                'origin' => 'manual',
                'occurred_on' => '2026-02-20',
                'amount' => 350000,
                'category' => 'Operasional',
                'description' => 'Konsumsi dan logistik rapat koordinasi kerja tengah periode.',
                'method' => 'cash',
                'department' => null,
                'program_id' => null,
                'actor_id' => $bendahara?->id,
                'receipt_path' => 'finance/nota_konsumsi_rapat.jpg',
                'receipt_name' => 'nota_konsumsi_rapat.jpg',
            ],
        ];

        foreach ($entries as $data) {
            FinanceEntry::updateOrCreate(['reference' => $data['reference']], $data);
        }

        // 3. Log Audit Keuangan
        DB::table('finance_audits')->insertOrIgnore([
            [
                'actor_id' => $bendahara?->id,
                'action' => 'monthly_reconciliation',
                'description' => 'Rekonsiliasi mutasi kas masuk dan keluar bulan Februari 2026 telah sinkron dan valid.',
                'created_at' => now()->subMonths(2),
            ],
            [
                'actor_id' => $bendahara?->id,
                'action' => 'cash_count',
                'description' => 'Pemeriksaan fisik brankas kas kecil sekretariat HIMSI bersama Pengawas.',
                'created_at' => now()->subMonth(),
            ],
        ]);
    }
}
