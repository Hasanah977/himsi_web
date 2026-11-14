<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_keluars', function (Blueprint $t) {
            $t->foreignId('ops_submission_id')->nullable()->unique()->constrained('ops_submissions')->restrictOnDelete();
        });
        Schema::table('pengawasan_laporans', function (Blueprint $t) {
            $t->foreignId('ops_program_id')->nullable()->constrained('ops_programs')->restrictOnDelete();
        });
        Schema::table('kbh_pendaftaran_seleksis', function (Blueprint $t) {
            $t->dateTime('interview_at')->nullable();
            $t->string('interview_location')->nullable();
            $t->unsignedTinyInteger('score')->nullable();
        });
        Schema::table('kbh_aspirasis', function (Blueprint $t) {
            $t->text('response')->nullable();
            $t->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kbh_aspirasis', function (Blueprint $t) {
            $t->dropConstrainedForeignId('responded_by');
            $t->dropColumn('response');
        });
        Schema::table('kbh_pendaftaran_seleksis', function (Blueprint $t) {
            $t->dropColumn(['interview_at', 'interview_location', 'score']);
        });
        Schema::table('pengawasan_laporans', function (Blueprint $t) {
            $t->dropConstrainedForeignId('ops_program_id');
        });
        Schema::table('surat_keluars', function (Blueprint $t) {
            $t->dropConstrainedForeignId('ops_submission_id');
        });
    }
};
