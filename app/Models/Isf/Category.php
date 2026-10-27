<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Category — taxonomical category for ISF content (Artikel, Infografik, Isu, dll.)
 */
class Category extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'order',
        'status',
    ];

    protected $attributes = [
        'status' => 'terbit',
        'order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }
}
