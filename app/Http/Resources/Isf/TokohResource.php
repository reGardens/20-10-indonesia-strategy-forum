<?php

namespace App\Http\Resources\Isf;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tokoh → "Profil & Tokoh Pekan Ini" page shape.
 *
 * @mixin \App\Models\Isf\Tokoh
 */
class TokohResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'position' => $this->position,
            'photoAlt' => $this->photo_alt ?? "Foto profil {$this->name}",
            'photoUrl' => $this->photo_url,
            'stats' => $this->stats ?? [],
            'skorSorotan' => [
                'value' => $this->skor_value,
                'period' => $this->skor_period,
                'explanation' => $this->skor_explanation,
            ],
            'policyMatrix' => [
                'columns' => $this->matrix_columns ?? [],
                'rows' => $this->matrix_rows ?? [],
            ],
            'relatedArticles' => $this->related_articles ?? [],
            'calendar' => [
                'year' => $this->calendar_year,
                'month' => $this->calendar_month,
                'events' => $this->calendar_events ?? [],
            ],
        ];
    }

    /**
     * Beranda "Pekan Ini" card for the featured tokoh ("Tokoh Pekan Ini").
     */
    public function pekanIniItem(): array
    {
        return [
            'type' => 'tokoh',
            'typeLabel' => 'Tokoh Pekan Ini',
            'title' => $this->name,
            'summary' => $this->teaser ?? '',
            'to' => "/tokoh/{$this->slug}",
        ];
    }
}
