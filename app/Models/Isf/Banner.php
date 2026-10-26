<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Banner — carousel slides on the public Beranda page.
 */
class Banner extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_banners';

    protected $fillable = [
        'kicker',
        'title',
        'deck',
        'to',
        'image_url',
        'image_label',
        'order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }
}
