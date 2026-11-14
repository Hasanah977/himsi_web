<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('surat_keluars', 'kategori_penomoran')) {
            Schema::table('surat_keluars', function (Blueprint $table) {
                $table->string('kategori_penomoran', 20)
                    ->nullable()
                    ->after('jenis_surat');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('surat_keluars', 'kategori_penomoran')) {
            Schema::table('surat_keluars', function (Blueprint $table) {
                $table->dropColumn('kategori_penomoran');
            });
        }
    }
};
