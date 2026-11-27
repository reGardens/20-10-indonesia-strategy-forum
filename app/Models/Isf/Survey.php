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
        'editorial_intro',
        'estimated_minutes',
        'questions',
        'summary',
        'expert_analysis',
        'chart_data',
        'pdf_path',
        'pdf_size',
        'order',
        'is_featured',
        'is_member_active',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_isf' => 'boolean',
            'is_featured' => 'boolean',
            'is_member_active' => 'boolean',
            'release_date' => 'date',
            'sample_size' => 'integer',
            'estimated_minutes' => 'integer',
            'order' => 'integer',
            'chart_data' => 'array',
            'questions' => 'array',
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

    public function responses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SurveyResponse::class, 'survey_id');
    }

    public function scopeMemberActive(Builder $query): Builder
    {
        return $query->where('is_member_active', true);
    }

    public function scopeRegion(Builder $query, ?string $region): Builder
    {
        if ($region && $region !== 'semua') {
            return $query->where('region', strtolower($region));
        }

        return $query;
    }
}

