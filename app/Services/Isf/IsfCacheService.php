<?php

namespace App\Services\Isf;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service terpusat untuk caching dan invalidasi konten publik ISF.
 * Mendukung driver file, database, maupun Redis (termasuk Unix socket).
 */
class IsfCacheService
{
    /** TTL Default: 15 menit (900 detik). */
    public const TTL_DEFAULT = 900;

    public const TAG_PUBLIC = 'isf-public';
    public const KEY_BERANDA = 'isf:public:beranda';
    public const KEY_INFOGRAFIK = 'isf:public:infografik';
    public const KEY_ANGGOTA = 'isf:public:anggota';
    public const PREFIX_TOKOH = 'isf:public:tokoh:';
    public const PREFIX_ISU = 'isf:public:isu:';

    /**
     * Mengambil data dari cache atau menjalankan callback dan menyimpannya.
     * Dilengkapi fallback otomatis jika koneksi cache/Redis bermasalah.
     */
    public static function remember(string $key, callable $callback, int $ttl = self::TTL_DEFAULT): mixed
    {
        try {
            if (static::supportsTags()) {
                return Cache::tags([self::TAG_PUBLIC])->remember($key, $ttl, $callback);
            }

            return Cache::remember($key, $ttl, $callback);
        } catch (Throwable $e) {
            Log::warning("ISF Cache error on key [{$key}]: " . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Bersihkan cache halaman Beranda.
     */
    public static function clearBeranda(): void
    {
        try {
            Cache::forget(self::KEY_BERANDA);
            if (static::supportsTags()) {
                Cache::tags([self::TAG_PUBLIC])->flush();
            }
        } catch (Throwable $e) {
            Log::warning('ISF Cache clearBeranda error: ' . $e->getMessage());
        }
    }

    /**
     * Bersihkan cache halaman Infografik.
     */
    public static function clearInfografik(): void
    {
        try {
            Cache::forget(self::KEY_INFOGRAFIK);
        } catch (Throwable $e) {
            Log::warning('ISF Cache clearInfografik error: ' . $e->getMessage());
        }
    }

    /**
     * Bersihkan cache profil Tokoh (dan beranda karena tokoh bisa tampil di pekan ini).
     */
    public static function clearTokoh(?string $slug = null): void
    {
        try {
            if ($slug) {
                Cache::forget(self::PREFIX_TOKOH . $slug);
            }
            self::clearBeranda();
        } catch (Throwable $e) {
            Log::warning('ISF Cache clearTokoh error: ' . $e->getMessage());
        }
    }

    /**
     * Bersihkan cache detail Isu (dan beranda karena isu bisa tampil di pekan ini).
     */
    public static function clearIsu(?string $slug = null): void
    {
        try {
            if ($slug) {
                Cache::forget(self::PREFIX_ISU . $slug);
            }
            self::clearBeranda();
        } catch (Throwable $e) {
            Log::warning('ISF Cache clearIsu error: ' . $e->getMessage());
        }
    }

    /**
     * Bersihkan cache dashboard Anggota.
     */
    public static function clearAnggota(): void
    {
        try {
            Cache::forget(self::KEY_ANGGOTA);
        } catch (Throwable $e) {
            Log::warning('ISF Cache clearAnggota error: ' . $e->getMessage());
        }
    }

    /**
     * Bersihkan seluruh cache konten publik ISF.
     */
    public static function clearAll(): void
    {
        try {
            Cache::forget(self::KEY_BERANDA);
            Cache::forget(self::KEY_INFOGRAFIK);
            Cache::forget(self::KEY_ANGGOTA);

            if (static::supportsTags()) {
                Cache::tags([self::TAG_PUBLIC])->flush();
            }
        } catch (Throwable $e) {
            Log::warning('ISF Cache clearAll error: ' . $e->getMessage());
        }
    }

    /**
     * Cek apakah driver cache saat ini mendukung tagging (seperti Redis).
     */
    protected static function supportsTags(): bool
    {
        try {
            return method_exists(Cache::getStore(), 'tags');
        } catch (Throwable) {
            return false;
        }
    }
}

