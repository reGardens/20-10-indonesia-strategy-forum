<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Infografik — Beranda "Infografik Terbaru".
 */
class Infographic extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_infographics';

    protected $fillable = [
        'slug',
        'title',
        'category',
        'published_at',
        'image_alt',
        'image_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date:Y-m-d',
        ];
    }
}
