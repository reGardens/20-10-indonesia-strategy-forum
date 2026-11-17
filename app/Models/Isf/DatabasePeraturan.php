<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatabasePeraturan extends Model
{
    use HasFactory, HasPublishStatus;

    protected $table = 'isf_database_peraturans';

    protected $fillable = [
        'judul',
        'nomor',
        'tahun',
        'kategori',
        'tentang',
        'url_dokumen',
        'lembaga',
        'urutan',
        'status',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];
}

