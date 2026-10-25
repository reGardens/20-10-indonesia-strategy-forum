<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Tokoh — "Profil & Tokoh Pekan Ini".
 */
class Tokoh extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_tokohs';

    protected $fillable = [
        'slug',
        'name',
        'position',
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
        'status',
    ];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
            'skor_value' => 'integer',
            'matrix_columns' => 'array',
            'matrix_rows' => 'array',
            'related_articles' => 'array',
            'calendar_year' => 'integer',
            'calendar_month' => 'integer',
            'calendar_events' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * The published "Tokoh Pekan Ini" (latest updated flagged one).
     */
    public static function currentFeatured(): ?self
    {
        return static::query()->terbit()->where('is_featured', true)
            ->orderByDesc('updated_at')->orderByDesc('id')->first();
    }
}
