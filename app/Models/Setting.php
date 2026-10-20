<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'wedding_id',
        'template_id',
        'theme_colors',
        'gallery_layout',
        'moderation_enabled',
        'password_protected',
        'password',
        'show_watermark',
        'custom_domain',
        'health_protocol',
        'live_stream_url',
        'shipping_address',
        'seo_meta',
        'qris_image_url',
    ];

    protected $casts = [
        'theme_colors' => 'array',
        'seo_meta' => 'array',
        'moderation_enabled' => 'boolean',
        'password_protected' => 'boolean',
        'show_watermark' => 'boolean',
    ];

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}
