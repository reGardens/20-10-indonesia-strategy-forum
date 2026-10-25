<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Alert Peringatan Dini — member dashboard feed.
 */
class Alert extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_alerts';

    protected $fillable = [
        'title',
        'summary',
        'priority',
        'date',
        'sektor',
        'link_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }
}
