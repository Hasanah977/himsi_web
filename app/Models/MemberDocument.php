<?php

namespace App\Models;

use Database\Factories\MemberDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberDocument extends Model
{
    /** @use HasFactory<MemberDocumentFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public const CATEGORIES = ['KRS', 'KTM', 'KTP', 'KHS', 'Sertifikat', 'Surat Aktif Kuliah', 'Bukti Iuran', 'Lainnya'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
