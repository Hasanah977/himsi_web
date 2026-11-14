<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ops_tasks', function (Blueprint $table): void {
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamp('completed_at')->nullable();
        });
        DB::table('ops_tasks')->whereIn('status', ['submitted', 'done'])->update(['progress' => 100]);
        Schema::table('public_contents', function (Blueprint $table): void {
            $table->string('department', 40)->nullable()->index();
        });
        Schema::create('announcement_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_id')->constrained('public_contents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['content_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
        Schema::table('public_contents', function (Blueprint $table): void {
            $table->dropIndex(['department']);
            $table->dropColumn('department');
        });
        Schema::table('ops_tasks', function (Blueprint $table): void {
            $table->dropColumn(['progress', 'completed_at']);
        });
    }
};
