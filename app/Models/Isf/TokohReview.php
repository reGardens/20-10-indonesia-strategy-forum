<?php

namespace App\Models\Isf;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TokohReview — ulasan & rating bintang "Indeks Tokoh".
 *
 * Pembagian tab: bintang 4–5 = "Tingkat Kesukaan", bintang 1–2 = "Tidak Suka".
 * Bintang 3 tetap dihitung di skor & sebaran, tapi tidak tampil di kedua tab.
 */
class TokohReview extends Model
{
    protected $table = 'isf_tokoh_reviews';

    public const SUKA_MIN = 4;

    public const TIDAK_SUKA_MAX = 2;

    protected $fillable = [
        'tokoh_id',
        'user_id',
        'rating',
        'body',
        'is_anonymous',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_anonymous' => 'boolean',
        ];
    }

    public function tokoh(): BelongsTo
    {
        return $this->belongsTo(Tokoh::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuka(Builder $query): Builder
    {
        return $query->where('rating', '>=', self::SUKA_MIN);
    }

    public function scopeTidakSuka(Builder $query): Builder
    {
        return $query->where('rating', '<=', self::TIDAK_SUKA_MAX);
    }
}
