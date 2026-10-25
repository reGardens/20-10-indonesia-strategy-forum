<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Data Grafik — bar chart dataset (SimpleBarChart.vue).
 */
class Chart extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_charts';

    protected $fillable = [
        'title',
        'description',
        'unit',
        'data',
        'source_title',
        'source_url',
        'is_beranda',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_beranda' => 'boolean',
        ];
    }

    /**
     * The published Beranda chart (latest updated flagged one).
     */
    public static function currentBeranda(): ?self
    {
        return static::query()->terbit()->where('is_beranda', true)
            ->orderByDesc('updated_at')->orderByDesc('id')->first();
    }
}
