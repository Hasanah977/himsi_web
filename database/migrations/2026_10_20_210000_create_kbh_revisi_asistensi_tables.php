<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kbh_periodes', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20); // voting / seleksi
            $table->string('nama', 150);
            $table->dateTime('mulai_pada');
            $table->dateTime('selesai_pada');
            $table->boolean('aktif')->default(false);
            $table->timestamps();
            $table->index(['jenis', 'aktif', 'mulai_pada', 'selesai_pada']);
        });

        Schema::create('kbh_pendaftaran_seleksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('kbh_periodes')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('khs_path', 500);
            $table->string('sertifikat_path', 500)->nullable();
            $table->string('status', 30)->default('menunggu');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['periode_id', 'user_id']);
        });

        Schema::create('kbh_aspirasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('judul', 150);
            $table->text('isi');
            $table->string('lampiran_path', 500)->nullable();
            $table->string('lampiran_nama', 255)->nullable();
            $table->string('lampiran_mime', 100)->nullable();
            $table->string('status', 30)->default('terkirim');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kbh_aspirasis');
        Schema::dropIfExists('kbh_pendaftaran_seleksis');
        Schema::dropIfExists('kbh_periodes');
    }
};
