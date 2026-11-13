<?php

namespace App\Support;

/**
 * Normalisasi & validasi nomor WhatsApp Indonesia.
 * Input "0812-3456-7890", "+62 812 3456 7890", "6281234567890" → disimpan "6281234567890".
 */
class WhatsAppNumber
{
    /** Pola nomor seluler Indonesia setelah normalisasi: 62 8xx, total 10–15 digit. */
    private const PATTERN = '/^628[1-9][0-9]{6,11}$/';

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    public static function isValid(?string $value): bool
    {
        $normalized = self::normalize($value);

        return $normalized !== null && preg_match(self::PATTERN, $normalized) === 1;
    }

    /** Untuk tampilan: 6281234567890 → +62 812-3456-7890 (3 digit awal, sisanya per 4 digit). */
    public static function display(?string $normalized): ?string
    {
        if (! $normalized) {
            return null;
        }

        $local = substr($normalized, 2);
        $groups = array_merge([substr($local, 0, 3)], str_split(substr($local, 3), 4));

        return '+62 ' . implode('-', array_filter($groups, fn ($g) => $g !== ''));
    }
}
