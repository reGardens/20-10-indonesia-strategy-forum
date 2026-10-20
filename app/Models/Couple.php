<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Couple extends Model
{
    protected $fillable = [
        'wedding_id',
        'groom_full_name',
        'groom_nickname',
        'groom_photo',
        'groom_father_name',
        'groom_mother_name',
        'groom_instagram_url',
        'groom_child_order',
        'bride_full_name',
        'bride_nickname',
        'bride_photo',
        'bride_father_name',
        'bride_mother_name',
        'bride_instagram_url',
        'bride_child_order',
        'music_url',
    ];

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}
