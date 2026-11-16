<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Kutipan Publik — Kumpulan kutipan tajam dari tokoh / netizen terkait isu strategis.
 */
class PublicQuote extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_public_quotes';

    protected $fillable = [
        'quote',
        'author_name',
        'author_title',
        'author_type',
        'tokoh_id',
        'topic',
        'source_title',
        'source_url',
        'quote_date',
        'order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'order' => 'integer',
        ];
    }

    public function tokoh(): BelongsTo
    {
        return $this->belongsTo(Tokoh::class, 'tokoh_id');
    }
}

