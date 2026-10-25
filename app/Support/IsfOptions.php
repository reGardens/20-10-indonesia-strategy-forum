<?php

namespace App\Support;

/**
 * Fixed option lists shared by ISF content validation and the CMS forms
 * (mirrored in resources/js/Pages/Cms/Isf/options.js).
 */
final class IsfOptions
{
    /** Content categories (CategoryLabel.vue). */
    public const CATEGORIES = ['politik', 'ekonomi', 'kebijakan', 'hukum', 'ruu'];

    /** Alert priorities: merah = Prioritas Tinggi, kuning = Perlu Dipantau. */
    public const PRIORITIES = ['merah', 'kuning'];

    /** Publish status: only "terbit" content is served by the public API. */
    public const STATUSES = ['draf', 'terbit'];
}
