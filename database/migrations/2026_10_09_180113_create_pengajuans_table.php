<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('judul');
            $table->string('jenis_pengajuan', 50);
            $table->text('deskripsi')->nullable();
            $table->decimal('nominal', 15, 2)->nullable();
            $table->string('lampiran')->nullable();

            $table->enum('status', [
                'menunggu',
                'disetujui',
                'ditolak',
            ])->default('menunggu');

            $table->foreignId('diproses_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('catatan_ketua')->nullable();
            $table->timestamp('diproses_pada')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuans');
    }
};
