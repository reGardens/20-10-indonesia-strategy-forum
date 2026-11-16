<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Model Kasus Induk untuk Timeline Kasus Hukum Besar.
 */
class CaseTimeline extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_case_timelines';

    protected $fillable = [
        'judul_kasus',
        'slug',
        'kategori_kasus',
        'deskripsi',
        'status_kasus',
        'gambar',
        'urutan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->judul_kasus);
            }
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(CaseEvent::class, 'case_timeline_id')
            ->orderBy('tanggal_peristiwa', 'asc')
            ->orderBy('urutan', 'asc');
    }
}

