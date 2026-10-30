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
        $result = static::rememberWithStatus($key, $callback, $ttl);

        return $result['data'];
    }

    /**
     * Mengambil data dari cache dan mengembalikan data beserta status HIT/MISS/ERROR.
     * Format return: ['data' => mixed, 'status' => 'HIT'|'MISS'|'FALLBACK_ERROR', 'driver' => string, 'error' => ?string]
     */
    public static function rememberWithStatus(string $key, callable $callback, int $ttl = self::TTL_DEFAULT): array
    {
        $driver = (string) config('cache.default', 'file');
        $isHit = false;

        try {
            if (static::supportsTags()) {
                $isHit = Cache::tags([self::TAG_PUBLIC])->has($key);
                $data = Cache::tags([self::TAG_PUBLIC])->remember($key, $ttl, $callback);
            } else {
                $isHit = Cache::has($key);
                $data = Cache::remember($key, $ttl, $callback);
            }

            return [
                'data' => $data,
                'status' => $isHit ? 'HIT' : 'MISS',
                'driver' => $driver,
                'error' => null,
            ];
        } catch (Throwable $e) {
            // Auto-heal Redis MISCONF (bgsave failure pada shared hosting)
            if (str_contains($e->getMessage(), 'MISCONF')) {
                if (static::healRedisMisconf()) {
                    try {
                        if (static::supportsTags()) {
                            $isHit = Cache::tags([self::TAG_PUBLIC])->has($key);
                            $data = Cache::tags([self::TAG_PUBLIC])->remember($key, $ttl, $callback);
                        } else {
                            $isHit = Cache::has($key);
                            $data = Cache::remember($key, $ttl, $callback);
                        }

                        return [
                            'data' => $data,
                            'status' => $isHit ? 'HIT' : 'MISS',
                            'driver' => $driver,
                            'error' => null,
                        ];
                    } catch (Throwable $retryException) {
                        Log::warning("ISF Cache retry after MISCONF heal failed: " . $retryException->getMessage());
                    }
                }
            }

            Log::warning("ISF Cache error on key [{$key}]: " . $e->getMessage());

            return [
                'data' => $callback(),
                'status' => 'FALLBACK_ERROR',
                'driver' => $driver,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Memperbaiki konfigurasi Redis jika mengalami error MISCONF (bgsave failure di shared hosting).
     * Memerintahkan Redis untuk mengabaikan error snapshot disk dan tetap mengizinkan write di RAM.
     */
    public static function healRedisMisconf(): bool
    {
        try {
            $connectionName = config('cache.stores.redis.connection', 'cache');
            $redis = \Illuminate\Support\Facades\Redis::connection($connectionName);

            // 1. Coba lewat command() Laravel facade
            try {
                $redis->command('config', ['SET', 'stop-writes-on-bgsave-error', 'no']);
                Log::info('ISF Cache: Redis stop-writes-on-bgsave-error set to no via command()');

                return true;
            } catch (Throwable) {
            }

            // 2. Coba lewat rawCommand()
            try {
                $redis->rawCommand('CONFIG', 'SET', 'stop-writes-on-bgsave-error', 'no');
                Log::info('ISF Cache: Redis stop-writes-on-bgsave-error set to no via rawCommand()');

                return true;
            } catch (Throwable) {
            }

            // 3. Coba akses native client phpredis
            if (method_exists($redis, 'client')) {
                $client = $redis->client();
                if (method_exists($client, 'config')) {
                    $client->config('SET', 'stop-writes-on-bgsave-error', 'no');
                    Log::info('ISF Cache: Redis stop-writes-on-bgsave-error set to no via client()->config()');

                    return true;
                }
                if (method_exists($client, 'rawCommand')) {
                    $client->rawCommand('CONFIG', 'SET', 'stop-writes-on-bgsave-error', 'no');
                    Log::info('ISF Cache: Redis stop-writes-on-bgsave-error set to no via client()->rawCommand()');

                    return true;
                }
            }

            return false;
        } catch (Throwable $e) {
            Log::warning('ISF Cache healRedisMisconf exception: ' . $e->getMessage());

            return false;
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
