<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicySector extends Model
{
    use HasFactory, HasPublishStatus;

    protected $table = 'isf_policy_sectors';

    protected $fillable = [
        'nama',
        'slug',
        'deskripsi',
        'urutan',
        'status',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];
}

