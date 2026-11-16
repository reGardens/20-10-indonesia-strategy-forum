<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Model Survey — Dashboard & Laporan Hasil Survei Opini Publik Nasional / Regional.
 */
class Survey extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_surveys';

    protected $fillable = [
        'title',
        'slug',
        'institution',
        'is_isf',
        'topic',
        'region',
        'survey_period',
        'release_date',
        'sample_size',
        'margin_of_error',
        'confidence_level',
        'sampling_method',
        'interview_method',
        'headline_stat_value',
        'headline_stat_label',
        'headline_stat_comparison',
        'summary',
        'expert_analysis',
        'chart_data',
        'pdf_path',
        'pdf_size',
        'order',
        'is_featured',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_isf' => 'boolean',
            'is_featured' => 'boolean',
            'release_date' => 'date',
            'sample_size' => 'integer',
            'order' => 'integer',
            'chart_data' => 'array',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (Survey $survey) {
            if (empty($survey->slug)) {
                $survey->slug = Str::slug($survey->title);
            }
        });
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeTopic(Builder $query, ?string $topic): Builder
    {
        if ($topic && $topic !== 'semua') {
            return $query->where('topic', strtolower($topic));
        }

        return $query;
    }

    public function scopeRegion(Builder $query, ?string $region): Builder
    {
        if ($region && $region !== 'semua') {
            return $query->where('region', strtolower($region));
        }

        return $query;
    }
}

