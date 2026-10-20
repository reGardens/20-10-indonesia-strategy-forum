<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'wedding_id',
        'name',
        'group',
        'code',
        'phone',
        'is_vip',
        'max_pax',
        'rsvp_status',
        'checked_in_at',
    ];

    protected $casts = [
        'is_vip' => 'boolean',
        'max_pax' => 'integer',
        'checked_in_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}
