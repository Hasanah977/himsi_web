<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_cash_locks', function (Blueprint $t) {
            $t->unsignedInteger('id')->primary();
        });
        DB::table('finance_cash_locks')->insert(['id' => 1]);
        Schema::create('finance_entries', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 120)->unique();
            $t->string('kind', 20);
            $t->string('origin', 30);
            $t->date('occurred_on')->index();
            $t->unsignedBigInteger('amount');
            $t->string('category', 100);
            $t->text('description');
            $t->string('method', 40);
            $t->string('department', 80)->nullable();
            $t->foreignId('program_id')->nullable()->constrained('ops_programs')->restrictOnDelete();
            $t->foreignId('submission_id')->nullable()->constrained('ops_submissions')->restrictOnDelete();
            $t->foreignId('dues_id')->nullable()->unique()->constrained('kbh_iuran_pembayarans')->restrictOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('receipt_path')->nullable();
            $t->string('receipt_name')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->text('void_reason')->nullable();
            $t->timestamps();
            $t->index(['kind', 'voided_at', 'occurred_on']);
        });
        Schema::create('finance_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 100);
            $t->text('description');
            $t->timestamp('created_at');
        });
        Schema::table('ops_submissions', function (Blueprint $t) {
            $t->foreignId('finance_approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('finance_approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ops_submissions', function (Blueprint $t) {
            $t->dropConstrainedForeignId('finance_approved_by');
            $t->dropColumn('finance_approved_at');
        });
        Schema::dropIfExists('finance_audits');
        Schema::dropIfExists('finance_entries');
        Schema::dropIfExists('finance_cash_locks');
    }
};
