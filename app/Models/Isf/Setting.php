<?php

namespace App\Models\Isf;

use App\Services\Isf\IsfCacheService;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan Halaman — singleton page copy stored as key + JSON value.
 *
 * Keys: "beranda" {topBar, pekanIni{dateStart,dateEnd}}, "anggota" {name,role,organization,plan,sektor[]}.
 */
class Setting extends Model
{
    protected $table = 'isf_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function () {
            IsfCacheService::clearAll();
        });

        static::deleted(function () {
            IsfCacheService::clearAll();
        });
    }

    /**
     * Read a setting value (array) or the given default when missing.
     */
    public static function get(string $key, array $default = []): array
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    /**
     * Create or replace a setting value.
     */
    public static function put(string $key, array $value): self
    {
        return static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
