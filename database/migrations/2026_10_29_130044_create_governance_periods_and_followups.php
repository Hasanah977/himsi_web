<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('governance_followups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('period_id')->constrained('governance_periods');
            $table->string('source_type', 80);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('author_id')->constrained('users');
            $table->text('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_followups');
        Schema::dropIfExists('governance_periods');
    }
};
