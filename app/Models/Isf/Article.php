<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use App\Support\IsfRubrik;
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
     * Kategori publik artikel (politik | hukum | kebijakan); nilai lama ruu/ekonomi ikut dipetakan.
     */
    public function categoryKey(): ?string
    {
        return IsfRubrik::resolve($this->category);
    }

    /**
     * Label kategori untuk kicker kartu (mis. "HUKUM") = kategori yang dipilih di CMS.
     */
    public function categoryLabel(): string
    {
        $key = $this->categoryKey();

        return mb_strtoupper($key ? IsfRubrik::label($key) : ((string) $this->category ?: 'Analisis'));
    }

    /**
     * Path halaman detail artikel: /artikel/{kategori}/{slug} (mis. /artikel/hukum/{slug}).
     */
    public function detailPath(): string
    {
        $category = $this->categoryKey();

        if (! $this->slug || ! $category) {
            return '/beranda';
        }

        return IsfRubrik::path($category) . '/' . $this->slug;
    }

    /**
     * Public URL of the article.
     *
     * Uses the CMS `link_url` override when set, except legacy `/isu/...` and
     * `/artikel/...` links (those resolve to the detail page) — otherwise detailPath().
     */
    public function publicUrl(): string
    {
        $link = trim((string) $this->link_url);

        if ($link !== '' && $link !== '/isu' && ! str_starts_with($link, '/isu/') && ! str_starts_with($link, '/artikel/')) {
            return $link;
        }

        return $this->detailPath();
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
