<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Publish status helpers for ISF content ("draf" / "terbit").
 */
trait HasPublishStatus
{
    /**
     * Only published ("terbit") rows.
     */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), 'terbit');
    }
}
