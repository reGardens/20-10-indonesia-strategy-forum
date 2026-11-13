<?php

namespace App\Support;

/**
 * Fixed option lists shared by ISF content validation and the CMS forms
 * (mirrored in resources/js/Pages/Cms/Isf/options.js).
 */
final class IsfOptions
{
    /**
     * Content categories (CategoryLabel.vue) = section Beranda = menu navbar.
     * ruu → hukum & ekonomi → kebijakan digabung (migration 2026_11_14_000003).
     */
    public const CATEGORIES = ['politik', 'kebijakan', 'hukum'];

    /** Alert priorities: merah = Prioritas Tinggi, kuning = Perlu Dipantau. */
    public const PRIORITIES = ['merah', 'kuning'];

    /** Publish status: only "terbit" content is served by the public API. */
    public const STATUSES = ['draf', 'terbit'];
}
