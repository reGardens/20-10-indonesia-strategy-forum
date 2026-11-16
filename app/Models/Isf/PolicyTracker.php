<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Model Policy Tracker — Monitoring Kebijakan (Janji vs Realisasi program pemerintah).
 */
class PolicyTracker extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_policy_trackers';

    protected $fillable = [
        'program_name',
        'slug',
        'sector',
        'promise',
        'realization',
        'progress_percent',
        'status_label',
        'source_title',
        'source_url',
        'last_updated_date',
        'order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'progress_percent' => 'integer',
            'last_updated_date' => 'date',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->program_name);
            }
        });
    }
}

