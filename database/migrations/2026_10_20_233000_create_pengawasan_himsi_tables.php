<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengawasan_laporans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->string('judul', 180);
            $table->string('nama_kegiatan', 180);
            $table->string('departemen', 100)->nullable();
            $table->longText('isi');
            $table->string('sumber', 30)->default('input_manual');
            $table->string('status', 30)->default('masuk');
            $table->string('lampiran_path')->nullable();
            $table->string('lampiran_nama')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
        Schema::create('pengawasan_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('pengawasan_laporans')->cascadeOnDelete();
            $table->foreignId('auditor_id')->constrained('users')->restrictOnDelete();
            $table->string('hasil', 30);
            $table->longText('catatan');
            $table->timestamps();
            $table->index(['laporan_id', 'created_at']);
        });
        Schema::create('pengawasan_rekomendasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('pengawasan_laporans')->cascadeOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->text('rekomendasi');
            $table->date('tenggat')->nullable();
            $table->string('status', 30)->default('terbuka');
            $table->longText('catatan_tindak_lanjut')->nullable();
            $table->timestamps();
            $table->index(['status', 'tenggat']);
        });
        Schema::create('pengawasan_eskalasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('pengawasan_laporans')->cascadeOnDelete();
            $table->foreignId('rekomendasi_id')->nullable()->constrained('pengawasan_rekomendasis')->nullOnDelete();
            $table->foreignId('diajukan_oleh')->constrained('users')->restrictOnDelete();
            $table->text('alasan');
            $table->string('status', 30)->default('menunggu');
            $table->longText('keputusan')->nullable();
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengawasan_eskalasis');
        Schema::dropIfExists('pengawasan_rekomendasis');
        Schema::dropIfExists('pengawasan_audits');
        Schema::dropIfExists('pengawasan_laporans');
    }
};
