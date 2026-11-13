<?php

namespace App\Support;

/**
 * Kategori artikel publik ISF = menu navbar (1 banding 1), SATU nama per kategori:
 * nama yang sama dipakai di navbar, breadcrumb, judul halaman, kicker kartu, dan CMS.
 *
 *   /artikel/{kategori}         = daftar artikel kategori (mis. /artikel/hukum)
 *   /artikel/{kategori}/{slug}  = detail artikel; breadcrumb kategori → /artikel/{kategori}
 *
 * Kategori lama "ruu" & "ekonomi" sudah digabung (migration 2026_11_14_000003);
 * LEGACY dipakai untuk mengalihkan URL lama /artikel/ruu/... & /artikel/ekonomi/...
 */
final class IsfRubrik
{
    /** Kategori (= IsfOptions::CATEGORIES) → nama (title = label = menu navbar) & deskripsi. */
    public const CATEGORIES = [
        'politik' => [
            'title' => 'Politik',
            'label' => 'Politik',
            'description' => 'Dinamika parlemen, partai, koalisi, dan arah legislasi nasional.',
        ],
        'hukum' => [
            'title' => 'Hukum',
            'label' => 'Hukum',
            'description' => 'Analisis yuridis, RUU, putusan pengadilan, dan kepatuhan regulasi.',
        ],
        'kebijakan' => [
            'title' => 'Kebijakan Publik',
            'label' => 'Kebijakan Publik',
            'description' => 'Kebijakan pemerintah, ekonomi, fiskal, dan dampaknya bagi dunia usaha.',
        ],
    ];

    /** Kategori lama → kategori gabungan. */
    public const LEGACY = [
        'ruu' => 'hukum',
        'ekonomi' => 'kebijakan',
    ];

    /** @return string[] */
    public static function keys(): array
    {
        return array_keys(self::CATEGORIES);
    }

    public static function exists(?string $category): bool
    {
        return $category !== null && isset(self::CATEGORIES[$category]);
    }

    /** Kategori aktif untuk sebuah nilai (termasuk kategori lama), null bila tidak dikenal. */
    public static function resolve(?string $category): ?string
    {
        $key = strtolower(trim((string) $category));
        $key = self::LEGACY[$key] ?? $key;

        return self::exists($key) ? $key : null;
    }

    /** Nilai kolom category yang termasuk kategori ini (kategori + nilai lama yang digabung). @return string[] */
    public static function categories(string $category): array
    {
        return [$category, ...array_keys(array_filter(self::LEGACY, fn ($to) => $to === $category))];
    }

    public static function path(string $category): string
    {
        return '/artikel/' . $category;
    }

    /** Label pendek untuk kicker kartu, mis. "Hukum". */
    public static function label(string $category): string
    {
        return self::CATEGORIES[$category]['label'] ?? ucfirst($category);
    }

    /** Bentuk publik {key, title, label, description, to}. */
    public static function summary(string $category): ?array
    {
        if (!self::exists($category)) {
            return null;
        }

        return [
            'key' => $category,
            'title' => self::CATEGORIES[$category]['title'],
            'label' => self::CATEGORIES[$category]['label'],
            'description' => self::CATEGORIES[$category]['description'],
            'to' => self::path($category),
        ];
    }
}
