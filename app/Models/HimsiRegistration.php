<?php

namespace App\Models;

use Database\Factories\HimsiRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HimsiRegistration extends Model
{
    /** @use HasFactory<HimsiRegistrationFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['review_version' => 'integer', 'document_reviews' => 'array', 'reviewed_at' => 'datetime', 'birth_date' => 'date', 'semester' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function labels(): array
    {
        return ['pending' => 'Perlu Review', 'returned' => 'Dikembalikan', 'approved' => 'Lolos KBH', 'rejected' => 'Ditolak'];
    }

    public static function documents(): array
    {
        return ['student_card' => 'KTM (Kartu Tanda Mahasiswa)', 'active_letter' => 'Surat Keterangan Mahasiswa Aktif', 'certificate' => 'Sertifikat Character Building'];
    }
}
