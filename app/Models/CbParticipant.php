<?php

namespace App\Models;

use Database\Factories\CbParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CbParticipant extends Model
{
    /** @use HasFactory<CbParticipantFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public static function labels(): array
    {
        return ['pending' => 'Menunggu Seleksi', 'accepted' => 'Diterima', 'rejected' => 'Ditolak'];
    }
}
