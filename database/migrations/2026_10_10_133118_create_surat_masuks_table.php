<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_masuks', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_surat', 100)->unique();
            $table->string('pengirim', 150);
            $table->string('perihal', 255);
            $table->date('tanggal_surat');
            $table->date('tanggal_diterima');
            $table->string('file_surat')->nullable();
            $table->text('keterangan')->nullable();

            $table->foreignId('dicatat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_masuks');
    }
};
