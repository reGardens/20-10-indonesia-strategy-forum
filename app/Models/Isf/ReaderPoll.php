<?php

namespace App\Models\Isf;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Model Reader Poll — Survei persepsi publik interaktif (non-ilmiah).
 */
class ReaderPoll extends Model
{
    protected $table = 'isf_reader_polls';

    protected $fillable = [
        'question',
        'slug',
        'description',
        'options',
        'start_date',
        'end_date',
        'status',
        'total_votes',
        'is_featured',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'total_votes' => 'integer',
            'is_featured' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReaderPollVote::class, 'poll_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->question);
            }
        });
    }
}

