<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Artikel — Beranda hero / Terkini, Isu Terpopuler.
 */
class Article extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_articles';

    protected $fillable = [
        'slug',
        'title',
        'category',
        'published_at',
        'summary',
        'body',
        'author_name',
        'author_role',
        'reading_minutes',
        'image_alt',
        'image_url',
        'link_url',
        'fact_blocks',
        'is_hero',
        'show_in_terkini',
        'is_popular',
        'popular_rank',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date:Y-m-d',
            'reading_minutes' => 'integer',
            'fact_blocks' => 'array',
            'is_hero' => 'boolean',
            'show_in_terkini' => 'boolean',
            'is_popular' => 'boolean',
            'popular_rank' => 'integer',
        ];
    }

    /**
     * The published Beranda hero article (latest published flagged one).
     */
    public static function currentHero(): ?self
    {
        return static::query()->terbit()->where('is_hero', true)
            ->orderByDesc('updated_at')->orderByDesc('published_at')->orderByDesc('id')->first();
    }
}
