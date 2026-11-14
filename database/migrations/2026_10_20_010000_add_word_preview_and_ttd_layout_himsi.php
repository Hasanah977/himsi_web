<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->string('file_pratinjau')->nullable();
            $table->unsignedSmallInteger('ttd_halaman')->nullable();
            $table->unsignedSmallInteger('ttd_lebar')->default(48);
            $table->unsignedSmallInteger('ttd_halaman_pendamping')->nullable();
            $table->unsignedSmallInteger('ttd_posisi_x_pendamping')->nullable();
            $table->unsignedSmallInteger('ttd_posisi_y_pendamping')->nullable();
            $table->unsignedSmallInteger('ttd_lebar_pendamping')->nullable();
        });
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->string('file_pratinjau')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->dropColumn(['file_pratinjau', 'ttd_halaman', 'ttd_lebar', 'ttd_halaman_pendamping', 'ttd_posisi_x_pendamping', 'ttd_posisi_y_pendamping', 'ttd_lebar_pendamping']);
        });
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->dropColumn('file_pratinjau');
        });
    }
};
