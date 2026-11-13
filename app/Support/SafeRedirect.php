<?php

namespace App\Support;

/**
 * Validasi parameter "redirect" (kembali ke halaman asal setelah login/register, mis. form ulasan Indeks Tokoh).
 *
 * Hanya path internal relatif ("/tokoh/x?tab=y") yang diterima, untuk mencegah open redirect
 * ("//evil.com", "/\evil.com", "https://..."). Selain itu → null (pakai tujuan default).
 * Pasangan frontend: resources/js/Utils/authRedirect.js (aturan sama).
 */
class SafeRedirect
{
    public static function path(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || strlen($value) > 500) {
            return null;
        }

        if (! preg_match('#^/(?![/\\\\])[A-Za-z0-9\-._~/%?=&+]*$#', $value)) {
            return null;
        }

        // Jangan kembali ke halaman login/logout/register itu sendiri.
        if (preg_match('#^/(login|logout|register)(?:[/?]|$)#', $value)) {
            return null;
        }

        return $value;
    }
}
