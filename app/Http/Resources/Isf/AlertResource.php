<?php

namespace App\Http\Resources\Isf;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Alert Peringatan Dini → member dashboard row.
 *
 * @mixin \App\Models\Isf\Alert
 */
class AlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'priority' => $this->priority,
            'title' => $this->title,
            'summary' => $this->summary,
            'date' => $this->date?->format('Y-m-d'),
            'sektor' => $this->sektor,
            'url' => $this->link_url,
        ];
    }
}
