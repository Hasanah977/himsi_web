<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('nomor_urut')->nullable();
            $table->string('nomor_surat', 150)->nullable()->unique();

            $table->string('jenis_surat', 20);
            $table->string('kode_lingkup', 10)->nullable();
            $table->string('kode_penerbit', 10)->nullable();

            $table->string('tujuan', 255);
            $table->string('perihal', 255);
            $table->date('tanggal_surat');

            $table->string('lampiran', 255)->nullable();
            $table->text('isi_surat')->nullable();

            $table->string('file_surat')->nullable();
            $table->string('file_final')->nullable();

            $table->enum('status', [
                'draft',
                'menunggu_persetujuan',
                'disetujui',
                'ditolak',
            ])->default('draft');

            $table->text('catatan_persetujuan')->nullable();
            $table->timestamp('diajukan_pada')->nullable();
            $table->timestamp('diputuskan_pada')->nullable();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('disetujui_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_keluars');
    }
};
