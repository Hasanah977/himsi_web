<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('himsi_registrations', function (Blueprint $table) {
            $table->json('document_reviews')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('review_version')->default(0);
        });
        Schema::create('cb_participants', function (Blueprint $table) {
            $table->id();
            $table->string('period', 40);
            $table->string('name', 120);
            $table->string('nim', 30);
            $table->unsignedSmallInteger('angkatan');
            $table->string('phone', 20)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('selection_note')->nullable();
            $table->timestamps();
            $table->unique(['period', 'nim']);
        });
        Schema::create('cb_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('period', 40);
            $table->string('location', 200);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 20)->default('planned');
            $table->text('description');
            $table->text('report')->nullable();
            $table->timestamps();
        });
        Schema::create('cb_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('cb_activities')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('cb_participants');
            $table->string('status', 20)->default('registered');
            $table->timestamps();
            $table->unique(['activity_id', 'participant_id']);
        });
        Schema::create('cb_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('cb_activities')->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('kind', 20);
            $table->string('path');
            $table->timestamps();
        });
        Schema::create('cb_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject')->nullable();
            $table->text('detail')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cb_logs');
        Schema::dropIfExists('cb_attachments');
        Schema::dropIfExists('cb_attendances');
        Schema::dropIfExists('cb_activities');
        Schema::dropIfExists('cb_participants');
        Schema::table('himsi_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['document_reviews', 'review_note', 'reviewed_at', 'review_version']);
        });
    }
};
