<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Model Glosarium Hukum (Kamus Istilah Interaktif).
 */
class LegalGlossary extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_legal_glossaries';

    protected $fillable = [
        'istilah',
        'slug',
        'abjad',
        'definisi',
        'dasar_hukum',
        'istilah_terkait',
        'urutan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'istilah_terkait' => 'array',
            'urutan' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->istilah);
            }
            if (empty($model->abjad) && !empty($model->istilah)) {
                $model->abjad = strtoupper(mb_substr(trim($model->istilah), 0, 1));
            }
        });
    }
}

