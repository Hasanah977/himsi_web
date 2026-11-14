<?php

namespace App\Models;

use Database\Factories\OpsGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OpsGroup extends Model
{
    /** @use HasFactory<OpsGroupFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ops_group_members', 'group_id', 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OpsMessage::class, 'group_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(OpsMessage::class, 'group_id')->latestOfMany();
    }
}
