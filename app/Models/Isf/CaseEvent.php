<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Peristiwa Kronologis Kasus Hukum.
 */
class CaseEvent extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_case_events';

    protected $fillable = [
        'case_timeline_id',
        'tanggal_peristiwa',
        'tahap',
        'judul',
        'deskripsi',
        'sumber_tautan',
        'urutan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_peristiwa' => 'date:Y-m-d',
            'urutan' => 'integer',
        ];
    }

    public function timeline(): BelongsTo
    {
        return $this->belongsTo(CaseTimeline::class, 'case_timeline_id');
    }
}

