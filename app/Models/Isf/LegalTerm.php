<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalTerm extends Model
{
    use HasFactory, HasPublishStatus;

    protected $table = 'isf_legal_terms';

    protected $appends = ['nama'];

    protected $fillable = [
        'istilah',
        'nama',
        'slug',
        'abjad',
        'kategori',
        'keterangan',
        'urutan',
        'status',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    public function getNamaAttribute(): ?string
    {
        return $this->attributes['istilah'] ?? null;
    }

    public function setNamaAttribute(?string $value): void
    {
        $this->attributes['istilah'] = $value;
        if (!empty($value) && empty($this->attributes['abjad'])) {
            $this->attributes['abjad'] = strtoupper(substr(trim($value), 0, 1));
        }
    }
}

