<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->unsignedInteger('nomor_urut')->nullable();
            $table->string('alamat_pengirim', 255)->nullable();
            $table->string('lampiran', 255)->nullable();
            $table->text('disposisi')->nullable();
            $table->string('bidang_arsip', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_urut',
                'alamat_pengirim',
                'lampiran',
                'disposisi',
                'bidang_arsip',
            ]);
        });
    }
};
