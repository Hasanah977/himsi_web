<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kbh_periodes', function (Blueprint $table) {
            $table->unsignedTinyInteger('batch')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('kbh_periodes')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('stage', 30)->default('registration');
            $table->timestamp('completed_at')->nullable();
            $table->unique(['parent_id', 'batch']);
        });
        Schema::table('kbh_pendaftaran_seleksis', function (Blueprint $table) {
            $table->string('khs_path', 500)->nullable()->change();
            $table->json('documents')->nullable();
            $table->string('position', 60)->nullable();
            $table->json('department_choices')->nullable();
            $table->json('department_reasons')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->json('interview_scores')->nullable();
            $table->text('interview_notes')->nullable();
            $table->string('assigned_department', 60)->nullable();
            $table->string('appointed_role', 60)->nullable();
            $table->timestamp('reviewed_at')->nullable();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('welcome_signature')->nullable();
        });
        Schema::table('kpum_candidates', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->unique(['election_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('kpum_candidates', function (Blueprint $table) {
            $table->dropUnique(['election_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('welcome_signature'));
        Schema::table('kbh_pendaftaran_seleksis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropColumn(['documents', 'position', 'department_choices', 'department_reasons', 'interview_scores', 'interview_notes', 'assigned_department', 'appointed_role', 'reviewed_at']);
        });
        Schema::table('kbh_periodes', function (Blueprint $table) {
            $table->dropUnique(['parent_id', 'batch']);
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['batch', 'stage', 'completed_at']);
        });
    }
};
