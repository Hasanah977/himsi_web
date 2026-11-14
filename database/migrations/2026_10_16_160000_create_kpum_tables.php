<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpum_elections', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Pemilihan Ketua Umum HIMSI');
            $table->string('period', 40)->default('');
            $table->string('status', 20)->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('certified_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('audit_note')->nullable();
            $table->timestamps();
        });
        Schema::create('kpum_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('kpum_elections');
            $table->unsignedInteger('number');
            $table->string('name', 120);
            $table->string('nim', 40);
            $table->unsignedSmallInteger('angkatan');
            $table->decimal('ipk', 3, 2);
            $table->text('vision');
            $table->text('mission');
            $table->string('photo')->nullable();
            $table->json('documents')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['election_id', 'nim']);
            $table->unique(['election_id', 'number']);
        });
        Schema::create('kpum_electors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('kpum_elections');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('has_voted')->default(false);
            $table->unique(['election_id', 'user_id']);
        });
        Schema::create('kpum_ballots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('election_id')->constrained('kpum_elections');
            $table->foreignId('candidate_id')->constrained('kpum_candidates');
            $table->timestamp('cast_at');
        });
        Schema::create('kpum_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('kpum_elections');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('detail')->nullable();
            $table->timestamp('created_at');
        });
        DB::table('kpum_elections')->insert([
            'id' => 1, 'title' => 'Pemilihan Ketua Umum HIMSI', 'period' => '',
            'status' => 'draft', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kpum_audits');
        Schema::dropIfExists('kpum_ballots');
        Schema::dropIfExists('kpum_electors');
        Schema::dropIfExists('kpum_candidates');
        Schema::dropIfExists('kpum_elections');
    }
};
