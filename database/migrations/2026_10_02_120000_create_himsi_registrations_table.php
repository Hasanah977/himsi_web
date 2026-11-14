<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('himsi_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nim', 30)->unique();
            $table->unsignedSmallInteger('angkatan');
            $table->string('program_studi')->default('Sistem Informasi');
            $table->string('phone', 20);
            $table->string('status', 30)->default('pending');
            $table->string('certificate_path');
            $table->string('active_letter_path');
            $table->string('student_card_path');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('himsi_registrations'); }
};
