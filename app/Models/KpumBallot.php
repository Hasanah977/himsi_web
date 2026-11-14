<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpumBallot extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = ['id'];
}
