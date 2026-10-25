<?php

namespace App\Http\Resources\Isf;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Artikel → public shapes used on Beranda (hero, Terkini) and the isu sidebar (Populer).
 *
 * Default toArray() = "Terkini" item. Use hero() / populer() for the other shapes.
 *
 * @mixin \App\Models\Isf\Article
 */
class ArticleResource extends JsonResource
{
    /**
     * "Terkini" item {title, category, publishedAt, to?}.
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'category' => $this->category,
            'publishedAt' => $this->published_at?->format('Y-m-d'),
            ...($this->link_url ? ['to' => $this->link_url] : []),
        ];
    }

    /**
     * Beranda hero / core article.
     */
    public function hero(): array
    {
        return [
            'slug' => $this->slug,
            'to' => $this->link_url,
            'category' => $this->category,
            'publishedAt' => $this->published_at?->format('Y-m-d'),
            'title' => $this->title,
            'summary' => $this->summary ?? '',
            'author' => [
                'name' => $this->author_name ?? '',
                'role' => $this->author_role ?? '',
            ],
            'readingMinutes' => $this->reading_minutes,
            'infographicAlt' => $this->image_alt ?? $this->title,
            'imageUrl' => $this->image_url,
        ];
    }

    /**
     * "Isu Terpopuler" item {title, to?}.
     */
    public function populer(): array
    {
        return [
            'title' => $this->title,
            ...($this->link_url ? ['to' => $this->link_url] : []),
        ];
    }
}
