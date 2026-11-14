<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $record = DB::table('public_contents')->where('section', 'settings')->where('slug', 'site')->first();
        if ($record) {
            $payload = json_decode($record->payload, true) ?? [];
            $payload['vision'] = 'Menjadikan HIMSI FT-UNSULBAR sebagai himpunan mahasiswa yang unggul dalam pengembangan akademik, pembinaan kader, dan penguatan organisasi, serta mampu menciptakan lingkungan yang kolaboratif, inovatif, dan berkelanjutan bagi mahasiswa Sistem Informasi.';
            $payload['missions'] = [
                'Menyelenggarakan program pengembangan akademik dan keterampilan yang mendukung peningkatan kompetensi mahasiswa Sistem Informasi.',
                'Membangun sistem kaderisasi yang berkelanjutan dengan menjunjung tinggi nilai-nilai organisasi dan budaya KMFT FT-UNSULBAR.',
                'Meningkatkan kualitas sumber daya mahasiswa melalui pengembangan hard skill dan soft skill yang relevan dengan bidang Sistem Informasi.',
                'Menumbuhkan rasa kekeluargaan, solidaritas, dan kepedulian antar mahasiswa Sistem Informasi untuk menciptakan lingkungan yang harmonis dan produktif.',
                'Menjadi wadah aspirasi mahasiswa yang terbuka, responsif, dan bertanggung jawab dalam memperjuangkan kepentingan bersama.',
            ];
            DB::table('public_contents')->where('id', $record->id)->update(['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        /** Content changes remain editable through Kelola Publik; rollback preserves the latest editorial content. */
    }
};
