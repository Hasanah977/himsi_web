<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persetujuan_tanda_tangan_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('menunggu');
            $table->text('catatan')->nullable();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->timestamps();
            $table->unique(['surat_keluar_id', 'user_id'], 'uniq_surat_penandatangan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persetujuan_tanda_tangan_surats');
    }
};
