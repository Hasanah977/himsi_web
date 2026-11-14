<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kpum_elections', function (Blueprint $table) {
            $table->boolean('registration_open')->default(false);
        });
        Schema::table('kpum_candidates', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpum_elections', function (Blueprint $table) {
            $table->dropColumn('registration_open');
        });
    }
};
