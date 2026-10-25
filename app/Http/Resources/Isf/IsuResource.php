<?php

namespace App\Http\Resources\Isf;

use App\Models\Isf\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Isu Mingguan → "Anatomi Isu Mingguan" page shape (7 frameworks + sidebar).
 *
 * @mixin \App\Models\Isf\Isu
 */
class IsuResource extends JsonResource
{
    /**
     * @param  \App\Models\Isf\Isu  $resource
     * @param  Collection<int, Article>|null  $populer  published "Isu Terpopuler" articles
     */
    public function __construct($resource, private readonly ?Collection $populer = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'category' => $this->category,
            'title' => $this->title,
            'summary' => $this->summary,
            'publishedAt' => $this->published_at?->format('Y-m-d'),
            'author' => $this->author,
            'frameworks' => [
                'ringkasan' => $this->ringkasan,
                'visualKunci' => $this->visualKunci(),
                'faktaKunci' => $this->fakta_kunci ?? [],
                'linimasa' => $this->linimasa ?? [],
                'aktor' => $this->aktor ?? [],
                'grc' => [
                    'governance' => $this->grc_governance,
                    'risk' => [
                        'level' => $this->grc_risk_level,
                        'description' => $this->grc_risk_description,
                    ],
                    'compliance' => $this->grc_compliance ?? [],
                ],
                'dampak' => [
                    'sektoral' => $this->dampak_sektoral ?? [],
                    'reaksi' => $this->dampak_reaksi ?? [],
                ],
                'selanjutnya' => [
                    'tahapan' => $this->selanjutnya_tahapan ?? [],
                    'pantau' => $this->selanjutnya_pantau ?? [],
                ],
            ],
            'populer' => ($this->populer ?? collect())
                ->map(fn (Article $article) => (new ArticleResource($article))->populer())
                ->values()
                ->all(),
            'metodologi' => [
                'text' => $this->metodologi_text,
                'signals' => $this->metodologi_signals ?? [],
            ],
            'faq' => $this->faq ?? [],
            'related' => $this->related ?? [],
        ];
    }

    /**
     * Beranda "Pekan Ini" card for the weekly isu ("Anatomi Isu").
     */
    public function pekanIniItem(): array
    {
        return [
            'type' => 'isu',
            'typeLabel' => 'Anatomi Isu',
            'title' => $this->title,
            'summary' => $this->teaser ?? '',
            'to' => "/isu/{$this->slug}",
        ];
    }

    /**
     * "02 Visual Kunci": image (alt/url) + the linked chart when it is published.
     * NULL when there is neither an image nor a published chart.
     */
    private function visualKunci(): ?array
    {
        $chart = $this->visualChart;
        $chart = $chart && $chart->status === 'terbit' ? $chart : null;

        if (! $chart && ! $this->visual_image_alt && ! $this->visual_image_url) {
            return null;
        }

        return [
            'imageAlt' => $this->visual_image_alt ?? '',
            'imageUrl' => $this->visual_image_url,
            ...($chart
                ? (new ChartResource($chart))->resolve()
                : ['title' => null, 'description' => '', 'unit' => '', 'data' => [], 'sourceTitle' => '', 'sourceUrl' => '']),
        ];
    }
}
