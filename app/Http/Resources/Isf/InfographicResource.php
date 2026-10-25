<?php

namespace App\Http\Resources\Isf;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Infografik → Beranda "Infografik Terbaru" card.
 *
 * @mixin \App\Models\Isf\Infographic
 */
class InfographicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'category' => $this->category,
            'publishedAt' => $this->published_at?->format('Y-m-d'),
            'alt' => $this->image_alt,
            'imageUrl' => $this->image_url,
        ];
    }
}
