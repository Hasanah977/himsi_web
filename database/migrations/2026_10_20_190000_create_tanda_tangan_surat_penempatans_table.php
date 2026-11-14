<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanda_tangan_surat_penempatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('gambar_path', 512);
            $table->unsignedSmallInteger('halaman');
            $table->decimal('x_mm', 7, 2);
            $table->decimal('y_mm', 7, 2);
            $table->decimal('lebar_mm', 6, 2);
            $table->timestamp('dikonfirmasi_pada');
            $table->timestamps();
            $table->unique(['surat_keluar_id', 'user_id'], 'ttd_surat_per_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanda_tangan_surat_penempatans');
    }
};
