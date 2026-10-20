<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'thumbnail_url',
        'config',
        'is_custom',
    ];

    protected $casts = [
        'config' => 'array',
        'is_custom' => 'boolean',
    ];
}
