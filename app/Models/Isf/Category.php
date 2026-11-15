<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Category — taxonomical category for ISF content (Artikel, Infografik, Isu, dll.)
 */
class Category extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_categories';

    protected $fillable = [
        'parent_id',
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
            'parent_id' => 'integer',
            'order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order', 'asc')->orderBy('id', 'asc');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSubcategories(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }
}
