<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Email Briefing — member email template content.
 */
class Briefing extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_briefings';

    protected $fillable = [
        'tanggal',
        'penerima',
        'organisasi',
        'ringkasan_judul',
        'ringkasan_catatan',
        'ringkasan_data',
        'pembuka',
        'poin',
        'penutup',
        'cta_url',
        'tautan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'ringkasan_data' => 'array',
            'poin' => 'array',
            'tautan' => 'array',
        ];
    }
}
