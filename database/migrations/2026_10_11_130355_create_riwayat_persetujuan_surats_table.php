<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_persetujuan_surats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('surat_keluar_id')
                ->constrained('surat_keluars')
                ->cascadeOnDelete();

            $table->unsignedInteger('pengajuan_ke');

            $table->string('status_keputusan', 30);

            $table->text('catatan')->nullable();

            $table->foreignId('diputuskan_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('diputuskan_pada')->nullable();

            $table->timestamps();

            $table->index(
                ['surat_keluar_id', 'pengajuan_ke'],
                'riwayat_surat_pengajuan_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_persetujuan_surats');
    }
};
