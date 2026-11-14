<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->string('jenis_pengajuan', 30)->default('surat_baru');
            $table->foreignId('penandatangan_pendamping_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('ttd_posisi_x')->nullable();
            $table->unsignedSmallInteger('ttd_posisi_y')->nullable();
            $table->timestamp('diarsipkan_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->dropForeign(['penandatangan_pendamping_id']);
            $table->dropColumn(['jenis_pengajuan', 'penandatangan_pendamping_id', 'ttd_posisi_x', 'ttd_posisi_y', 'diarsipkan_pada']);
        });
    }
};
