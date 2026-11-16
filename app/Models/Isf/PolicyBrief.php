<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Model Policy Brief — Ringkasan eksekutif riset kebijakan publik & unduh dokumen PDF.
 */
class PolicyBrief extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_policy_briefs';

    protected $fillable = [
        'title',
        'slug',
        'sector',
        'author',
        'institution',
        'executive_summary',
        'key_recommendations',
        'pdf_path',
        'pdf_size',
        'published_at',
        'order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'key_recommendations' => 'array',
            'published_at' => 'date',
            'order' => 'integer',
        ];
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

