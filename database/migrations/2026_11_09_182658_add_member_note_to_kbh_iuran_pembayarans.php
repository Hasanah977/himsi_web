<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kbh_iuran_pembayarans', function (Blueprint $table): void {
            $table->string('catatan_anggota', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('kbh_iuran_pembayarans', function (Blueprint $table): void {
            $table->dropColumn('catatan_anggota');
        });
    }
};
