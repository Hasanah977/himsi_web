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
        Schema::create('meetings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title', 160);
            $table->string('category', 30);
            $table->dateTime('scheduled_at');
            $table->string('location', 200);
            $table->string('status', 20)->default('scheduled');
            $table->timestamp('attendance_opened_at')->nullable();
            $table->timestamp('attendance_closed_at')->nullable();
            $table->text('minutes')->nullable();
            $table->timestamps();
            $table->index(['category', 'scheduled_at']);
        });
        Schema::create('meeting_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('participant_name');
            $table->string('participant_role', 50);
            $table->string('participant_department', 40)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('source', 20)->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
            $table->unique(['meeting_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendances');
        Schema::dropIfExists('meetings');
    }
};
