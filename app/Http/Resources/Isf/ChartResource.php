<?php

namespace App\Http\Resources\Isf;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Data Grafik → SimpleBarChart props {title, description, unit, data, sourceTitle, sourceUrl}.
 *
 * @mixin \App\Models\Isf\Chart
 */
class ChartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description ?? '',
            'unit' => $this->unit ?? '',
            'data' => $this->data ?? [],
            'sourceTitle' => $this->source_title ?? '',
            'sourceUrl' => $this->source_url ?? '',
        ];
    }
}
