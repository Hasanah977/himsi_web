<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['foto_profile', 'phone'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column): void {
                    $table->string($column)->nullable();
                });
            }
        }
        Schema::create('kbh_activity_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('route');
            $table->timestamp('seen_at');
            $table->unique(['user_id', 'route']);
        });
        DB::table('ops_groups')->insertOrIgnore(['system_key' => 'kbh', 'department' => 'kbh', 'name' => 'Keluarga Besar HIMSI', 'description' => 'Ruang diskusi seluruh anggota KBH.', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Preserve profile columns that may already exist in older installations.
        Schema::dropIfExists('kbh_activity_reads');
        DB::table('ops_groups')->where('system_key', 'kbh')->delete();
    }
};
