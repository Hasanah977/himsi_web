<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kbh_iuran_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->unsignedInteger('nominal');
            $table->string('status', 30)->default('menunggu_verifikasi');
            $table->string('bukti_path', 500);
            $table->string('bukti_nama', 255);
            $table->string('bukti_mime', 100);
            $table->text('catatan_bendahara')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'tahun', 'bulan'], 'uniq_kbh_iuran_user_tahun_bulan');
            $table->index(['status', 'tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kbh_iuran_pembayarans');
    }
};
