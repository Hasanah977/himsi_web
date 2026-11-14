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
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 60)->nullable();
            $table->string('event', 80)->index();
            $table->string('severity', 20)->default('info')->index();
            $table->string('summary');
            $table->string('ip_address', 45)->nullable();
            $table->string('route')->nullable();
            $table->unsignedInteger('affected_count')->default(0);
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['actor_id', 'event', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
