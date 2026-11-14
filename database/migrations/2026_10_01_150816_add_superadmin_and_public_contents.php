<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 60)
                ->default('pending')
                ->index();

            $table->string('department', 60)->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('public_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section', 60)->index();
            $table->string('slug', 150)->nullable();
            $table->json('payload');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->unique(['section', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_contents');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'department',
                'is_active',
            ]);
        });
    }
};
