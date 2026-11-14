<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('governance_period_id')->nullable()->constrained('governance_periods')->nullOnDelete();
            $table->string('source_key')->unique();
            $table->string('role', 80);
            $table->string('department', 80)->nullable();
            $table->string('period_label');
            $table->timestamp('appointed_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'appointed_at']);
        });

        $applications = DB::table('kbh_pendaftaran_seleksis as a')
            ->join('kbh_periodes as p', 'p.id', '=', 'a.periode_id')
            ->where('a.status', 'lolos')->whereNotNull('a.appointed_role')
            ->select('a.*', 'p.nama', 'p.completed_at')->orderBy('a.id')->get();
        foreach ($applications as $application) {
            $this->record($application->user_id, $application->appointed_role, $application->assigned_department,
                $application->nama, $application->completed_at ?? $application->updated_at, 'selection:'.$application->id);
        }
        foreach (DB::table('kpum_elections')->where('status', 'published')->get() as $election) {
            $candidates = DB::table('kpum_candidates')->where('election_id', $election->id)->where('status', 'approved')->get()
                ->map(function (object $candidate): object {
                    $candidate->votes = DB::table('kpum_ballots')->where('candidate_id', $candidate->id)->count();

                    return $candidate;
                });
            $winners = $candidates->where('votes', $candidates->max('votes'));
            if ($winners->count() === 1 && $winners->first()->votes > 0 && $winners->first()->user_id) {
                $this->record($winners->first()->user_id, 'ketua_umum', null, $election->period,
                    $election->published_at ?? $election->updated_at, 'election:'.$election->id.':'.$election->period);
            }
        }
    }

    private function record(int $userId, string $role, ?string $department, string $label, string $appointedAt, string $source): void
    {
        $period = DB::table('governance_periods')->where('starts_at', '<=', $appointedAt)->where('ends_at', '>=', $appointedAt)->orderByDesc('id')->first();
        DB::table('member_appointments')->insert([
            'user_id' => $userId, 'governance_period_id' => $period?->id, 'source_key' => $source,
            'role' => $role, 'department' => $department, 'period_label' => $period?->name ?? $label,
            'appointed_at' => $appointedAt, 'ended_at' => $period?->closed_at,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('member_appointments');
    }
};
