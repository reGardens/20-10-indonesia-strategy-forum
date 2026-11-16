<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tokoh — "Profil & Tokoh Pekan Ini" serta Rubrik Sosok (/sosok).
 */
class Tokoh extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_tokohs';

    public const ROLE_CATEGORIES = [
        'eksekutif' => 'Eksekutif',
        'legislatif' => 'Legislatif',
        'yudikatif' => 'Yudikatif',
        'pakar' => 'Akademisi / Pakar',
        'militer' => 'Militer / Hankam',
        'pengusaha' => 'Pengusaha',
    ];

    public const RESERVED_CATEGORIES = [
        'eksekutif',
        'legislatif',
        'yudikatif',
        'pakar',
        'militer',
        'pengusaha',
    ];

    protected $fillable = [
        'slug',
        'name',
        'position',
        'role_category',
        'issues',
        'summary_analysis',
        'bio',
        'career_timeline',
        'education',
        'positions_history',
        'influence_score',
        'affiliations',
        'allies',
        'critics',
        'quotes',
        'controversies',
        'aliases',
        'photo_alt',
        'photo_url',
        'stats',
        'skor_value',
        'skor_period',
        'skor_explanation',
        'matrix_columns',
        'matrix_rows',
        'related_articles',
        'calendar_year',
        'calendar_month',
        'calendar_events',
        'teaser',
        'is_featured',
        'is_influential_weekly',
        'show_in_politik',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issues' => 'array',
            'career_timeline' => 'array',
            'education' => 'array',
            'positions_history' => 'array',
            'influence_score' => 'integer',
            'affiliations' => 'array',
            'allies' => 'array',
            'critics' => 'array',
            'quotes' => 'array',
            'controversies' => 'array',
            'aliases' => 'array',
            'stats' => 'array',
            'skor_value' => 'integer',
            'matrix_columns' => 'array',
            'matrix_rows' => 'array',
            'related_articles' => 'array',
            'calendar_year' => 'integer',
            'calendar_month' => 'integer',
            'calendar_events' => 'array',
            'is_featured' => 'boolean',
            'is_influential_weekly' => 'boolean',
            'show_in_politik' => 'boolean',
        ];
    }

    /**
     * Scope to filter by role category.
     */
    public function scopeRoleCategory(Builder $query, ?string $category): Builder
    {
        if (! $category || $category === 'semua') {
            return $query;
        }

        return $query->where('role_category', strtolower(trim($category)));
    }

    /**
     * Scope to filter by issue (e.g. ekonomi, hukum, pertahanan, politik).
     */
    public function scopeIssue(Builder $query, ?string $issue): Builder
    {
        if (! $issue || $issue === 'semua') {
            return $query;
        }

        $term = strtolower(trim($issue));

        return $query->where(function (Builder $q) use ($term) {
            $q->whereJsonContains('issues', $term)
              ->orWhere('summary_analysis', 'like', "%{$term}%")
              ->orWhere('position', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to filter by starting alphabet letter (A-Z).
     */
    public function scopeAlphabet(Builder $query, ?string $letter): Builder
    {
        if (! $letter || $letter === 'semua' || strlen($letter) !== 1) {
            return $query;
        }

        return $query->where('name', 'like', strtoupper($letter) . '%');
    }

    /**
     * Scope for Tokoh Paling Berpengaruh Minggu Ini (Hero).
     */
    public function scopeInfluentialWeekly(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('is_influential_weekly', true)
              ->orWhere('is_featured', true);
        });
    }

    /**
     * The published "Tokoh Pekan Ini" / "Tokoh Paling Berpengaruh Minggu Ini".
     */
    public static function currentFeatured(): ?self
    {
        return static::query()->terbit()
            ->influentialWeekly()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first() ?? static::query()->terbit()->orderByDesc('influence_score')->first();
    }
}
