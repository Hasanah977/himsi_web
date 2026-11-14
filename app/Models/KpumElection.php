<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KpumElection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'closed_at' => 'datetime', 'certified_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function acceptingVotes(): bool
    {
        return $this->status === 'open' && $this->starts_at && $this->ends_at
            && now()->gte($this->starts_at) && now()->lt($this->ends_at);
    }

    public function finished(): bool
    {
        return in_array($this->status, ['closed', 'certified', 'published'], true)
            || ($this->status === 'open' && $this->ends_at && now()->gte($this->ends_at));
    }

    public function label(): string
    {
        if ($this->status === 'open') {
            return $this->finished() ? 'Voting berakhir' : ($this->acceptingVotes() ? 'Voting berlangsung' : 'Terjadwal');
        }

        return ['draft' => 'Persiapan', 'closed' => 'Voting ditutup', 'certified' => 'Hasil disahkan', 'published' => 'Hasil dipublikasikan'][$this->status] ?? $this->status;
    }

    public function requireDraft(): void
    {
        if ($this->status !== 'draft') {
            throw ValidationException::withMessages(['kpum' => 'Data terkunci karena pemilihan sudah diaktifkan.']);
        }
    }

    public function record(string $action, ?string $detail = null): void
    {
        DB::table('kpum_audits')->insert(['election_id' => $this->id, 'actor_id' => auth()->id(), 'action' => $action, 'detail' => $detail, 'created_at' => now()]);
    }

    public function tally(): Collection
    {
        return KpumCandidate::where('election_id', $this->id)->where('status', 'approved')
            ->withCount('ballots')->orderBy('number')->get();
    }

    public function checks(): array
    {
        $ballots = DB::table('kpum_ballots')->where('election_id', $this->id);
        $total = (clone $ballots)->count();
        $used = DB::table('kpum_electors')->where('election_id', $this->id)->where('has_voted', true)->count();
        $eligible = DB::table('kpum_electors')->where('election_id', $this->id)->count();
        $validCandidates = KpumCandidate::where('election_id', $this->id)->where('status', 'approved')->pluck('id');

        return [
            'Jumlah suara sesuai jumlah pemilih yang sudah memilih' => $total === $used,
            'Jumlah suara tidak melebihi daftar pemilih' => $total <= $eligible,
            'Semua suara diberikan kepada kandidat terverifikasi' => ! (clone $ballots)->whereNotIn('candidate_id', $validCandidates)->exists(),
            'Semua suara berada dalam jadwal pemilihan' => $this->starts_at && $this->ends_at && ! (clone $ballots)->where(function ($q) {
                $q->where('cast_at', '<', $this->starts_at)->orWhere('cast_at', '>=', $this->ends_at);
                if ($this->closed_at) {
                    $q->orWhere('cast_at', '>', $this->closed_at);
                }
            })->exists(),
        ];
    }
}
