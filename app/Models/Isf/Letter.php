<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Model Surat Pembaca — Kontribusi opini & aspirasi publik dengan alur moderasi redaksi.
 */
class Letter extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_letters';

    protected $fillable = [
        'sender_name',
        'sender_email',
        'sender_city',
        'title',
        'slug',
        'body',
        'moderation_status',
        'moderated_at',
        'moderated_by',
        'moderation_notes',
        'published_at',
        'order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'moderated_at' => 'datetime',
            'published_at' => 'datetime',
            'order' => 'integer',
        ];
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopeDisetujui(Builder $query): Builder
    {
        return $query->where('moderation_status', 'disetujui');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('moderation_status', 'pending');
    }

    public function scopeDitolak(Builder $query): Builder
    {
        return $query->where('moderation_status', 'ditolak');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
        });
    }
}

