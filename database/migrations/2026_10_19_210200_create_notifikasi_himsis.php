<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_himsis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 180);
            $table->text('pesan');
            $table->string('url', 500)->nullable();
            $table->string('kategori', 40)->default('umum');
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'dibaca_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_himsis');
    }
};
