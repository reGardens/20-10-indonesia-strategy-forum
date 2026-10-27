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
            'id' => (string) $this->id,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'author' => [
                'name' => $this->author_name ?: 'Budi Santoso',
                'role' => $this->author_role ?: 'Analis Regulasi & Kebijakan',
                'avatar' => $this->author_avatar ?: '',
            ],
            'readMoreUrl' => $this->read_more_url ?? '',
            'unit' => $this->unit ?? '',
            'data' => $this->data ?? [],
            'sourceTitle' => $this->source_title ?? '',
            'sourceUrl' => $this->source_url ?? '',
            'is_beranda' => (bool) $this->is_beranda,
            'order' => (int) ($this->order ?? 0),
            'status' => $this->status,
        ];
    }
}
