<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpumCandidate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['documents' => 'array', 'ipk' => 'decimal:2'];
    }

    public function ballots(): HasMany
    {
        return $this->hasMany(KpumBallot::class, 'candidate_id');
    }

    public static function documentLabels(): array
    {
        return ['ktm' => 'Kartu Tanda Mahasiswa', 'transcript' => 'Transkrip nilai', 'propesif' => 'Sertifikat Propesif'];
    }

    public function label(): string
    {
        return ['pending' => 'Menunggu verifikasi', 'approved' => 'Terverifikasi', 'rejected' => 'Perlu perbaikan'][$this->status] ?? $this->status;
    }
}
