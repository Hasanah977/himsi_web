<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pprk_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('department', 40);
            $table->string('period', 40);
            $table->string('status', 30)->default('draft');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(0);
            $table->unsignedInteger('revision')->default(0);
            $table->json('reviews')->nullable();
            $table->json('history')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['department', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pprk_submissions');
    }
};
