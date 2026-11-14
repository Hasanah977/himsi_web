<?php

use Database\Seeders\OpsGroupsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ops_programs', function (Blueprint $t) {
            $t->id();
            $t->string('department', 40);
            $t->string('period', 40);
            $t->string('title', 160);
            $t->text('description');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('location', 200);
            $t->string('target', 200);
            $t->foreignId('pic_id')->constrained('users');
            $t->foreignId('created_by')->constrained('users');
            $t->string('status', 30)->default('draft');
            $t->unsignedInteger('version')->default(0);
            $t->timestamps();
            $t->index(['department', 'status']);
        });
        Schema::create('ops_program_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('program_id')->constrained('ops_programs')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users');
            $t->unique(['program_id', 'user_id']);
        });
        Schema::create('ops_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('program_id')->constrained('ops_programs')->cascadeOnDelete();
            $t->string('type', 12);
            $t->string('status', 30)->default('draft');
            $t->json('payload')->nullable();
            $t->unsignedBigInteger('total')->default(0);
            $t->string('file_path')->nullable();
            $t->string('file_name')->nullable();
            $t->text('note')->nullable();
            $t->unsignedInteger('version')->default(0);
            $t->timestamps();
            $t->unique(['program_id', 'type']);
            $t->index(['type', 'status']);
        });
        Schema::create('ops_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('program_id')->constrained('ops_programs')->cascadeOnDelete();
            $t->foreignId('assigned_to')->constrained('users');
            $t->foreignId('created_by')->constrained('users');
            $t->string('title', 160);
            $t->text('description');
            $t->date('deadline');
            $t->string('status', 30)->default('assigned');
            $t->text('result')->nullable();
            $t->string('file_path')->nullable();
            $t->string('file_name')->nullable();
            $t->text('feedback')->nullable();
            $t->unsignedInteger('version')->default(0);
            $t->timestamps();
            $t->index(['assigned_to', 'status']);
        });
        Schema::create('ops_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('program_id')->constrained('ops_programs')->cascadeOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 100);
            $t->text('note')->nullable();
            $t->json('snapshot')->nullable();
            $t->timestamp('created_at');
        });
        Schema::create('ops_groups', function (Blueprint $t) {
            $t->id();
            $t->string('system_key', 60)->nullable()->unique();
            $t->string('department', 40)->nullable();
            $t->string('name', 120);
            $t->text('description')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('ops_group_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('group_id')->constrained('ops_groups')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->unique(['group_id', 'user_id']);
        });
        Schema::create('ops_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('group_id')->constrained('ops_groups')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('body')->nullable();
            $t->string('file_path')->nullable();
            $t->string('file_name')->nullable();
            $t->timestamps();
        });
        Schema::create('ops_reads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('group_id')->constrained('ops_groups')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedBigInteger('last_message_id')->default(0);
            $t->unique(['group_id', 'user_id']);
        });
        (new OpsGroupsSeeder)->run();
    }

    public function down(): void
    {
        foreach (['ops_reads', 'ops_messages', 'ops_group_members', 'ops_groups', 'ops_history', 'ops_tasks', 'ops_submissions', 'ops_program_members', 'ops_programs'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
