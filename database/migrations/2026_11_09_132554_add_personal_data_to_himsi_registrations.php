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
        Schema::table('himsi_registrations', function (Blueprint $table) {
            Schema::table('himsi_registrations', function (Blueprint $table): void {
                $table->string('birth_place')->nullable();
                $table->date('birth_date')->nullable();
                $table->unsignedTinyInteger('semester')->nullable();
                $table->string('gender', 20)->nullable();
                $table->string('religion', 40)->nullable();
                $table->string('guardian_name')->nullable();
                $table->string('guardian_phone', 20)->nullable();
                $table->string('nik', 16)->nullable();
                $table->string('nisn', 10)->nullable();
                $table->string('npwp', 20)->nullable();
                $table->string('address_street')->nullable();
                $table->string('address_rt', 3)->nullable();
                $table->string('address_rw', 3)->nullable();
                $table->string('address_hamlet')->nullable();
                $table->string('address_village')->nullable();
                $table->string('address_district')->nullable();
                $table->string('address_city')->nullable();
                $table->string('address_province')->nullable();
                $table->string('address_postcode', 5)->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('himsi_registrations', function (Blueprint $table) {
            Schema::table('himsi_registrations', function (Blueprint $table): void {
                $table->dropColumn(['birth_place', 'birth_date', 'semester', 'gender', 'religion', 'guardian_name', 'guardian_phone', 'nik', 'nisn', 'npwp', 'address_street', 'address_rt', 'address_rw', 'address_hamlet', 'address_village', 'address_district', 'address_city', 'address_province', 'address_postcode']);
            });
        });
    }
};
