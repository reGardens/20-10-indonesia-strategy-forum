<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * ParliamentSeat — Peta Kursi Parlemen (DPR/DPD/MPR) per partai/fraksi.
 */
class ParliamentSeat extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_parliament_seats';

    protected $fillable = [
        'periode',
        'lembaga',
        'partai',
        'singkatan',
        'jumlah_kursi',
        'warna_partai',
        'logo',
        'urutan',
        'status',
    ];

    protected $attributes = [
        'periode' => '2024-2029',
        'lembaga' => 'DPR',
        'jumlah_kursi' => 0,
        'warna_partai' => '#E10600',
        'urutan' => 0,
        'status' => 'terbit',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_kursi' => 'integer',
            'urutan' => 'integer',
        ];
    }
}

