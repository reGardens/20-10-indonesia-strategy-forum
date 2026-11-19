<?php

namespace App\Models\Isf;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Iklan Beranda: spanduk (slot banner kolom kiri) dan modal popup.
 *
 * Tayang ("live") bila: status active, sekarang di dalam rentang start_at–end_at (null = tanpa batas),
 * dan di dalam jam tayang harian (null = sepanjang hari; boleh melewati tengah malam).
 */
class Ad extends Model
{
    public const PLACEMENT_SPANDUK = 'spanduk';
    public const PLACEMENT_POPUP = 'popup';
    public const PLACEMENTS = [self::PLACEMENT_SPANDUK, self::PLACEMENT_POPUP];

    public const TYPES = ['banner', 'video'];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_EXPIRED = 'expired';
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_PAUSED, self::STATUS_EXPIRED];

    protected $table = 'isf_ads';

    protected $fillable = [
        'title',
        'kicker',
        'placement',
        'type',
        'image_url',
        'video_url',
        'video_duration',
        'target_url',
        'start_at',
        'end_at',
        'active_hours_start',
        'active_hours_end',
        'status',
        'sort_order',
        'first_delay_seconds',
        'interval_minutes',
        'is_skippable',
        'skip_delay_seconds',
    ];

    protected $attributes = [
        'type' => 'banner',
        'status' => self::STATUS_ACTIVE,
        'sort_order' => 0,
        'impressions' => 0,
        'clicks' => 0,
        'is_skippable' => true,
        'skip_delay_seconds' => 10,
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'video_duration' => 'integer',
            'sort_order' => 'integer',
            'first_delay_seconds' => 'integer',
            'interval_minutes' => 'integer',
            'is_skippable' => 'boolean',
            'skip_delay_seconds' => 'integer',
            'impressions' => 'integer',
            'clicks' => 'integer',
        ];
    }

    /** Iklan yang sedang tayang sekarang (status, rentang tanggal, dan jam tayang harian). */
    public function scopeLive(Builder $query, ?CarbonInterface $now = null): Builder
    {
        $now ??= now();
        $time = $now->format('H:i:s');

        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(fn (Builder $q) => $q->whereNull('start_at')->orWhere('start_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('end_at')->orWhere('end_at', '>', $now))
            ->where(function (Builder $q) use ($time) {
                $q->whereNull('active_hours_start')
                    ->orWhereNull('active_hours_end')
                    // Jam normal (08:00–17:00).
                    ->orWhere(fn (Builder $w) => $w
                        ->whereColumn('active_hours_start', '<=', 'active_hours_end')
                        ->where('active_hours_start', '<=', $time)
                        ->where('active_hours_end', '>', $time))
                    // Melewati tengah malam (22:00–02:00).
                    ->orWhere(fn (Builder $w) => $w
                        ->whereColumn('active_hours_start', '>', 'active_hours_end')
                        ->where(fn (Builder $o) => $o
                            ->where('active_hours_start', '<=', $time)
                            ->orWhere('active_hours_end', '>', $time)));
            });
    }

    public function scopePlacement(Builder $query, string $placement): Builder
    {
        return $query->where('placement', $placement);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Ubah status iklan aktif yang tanggal selesainya sudah lewat menjadi expired. */
    public static function expireOverdue(?CarbonInterface $now = null): int
    {
        return static::query()
            ->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('end_at')
            ->where('end_at', '<=', $now ?? now())
            ->update(['status' => self::STATUS_EXPIRED, 'updated_at' => now()]);
    }

    /**
     * Pilih satu iklan secara acak (peluang sama) untuk rotasi spanduk.
     *
     * @param  Collection<int, Ad>  $ads
     */
    public static function pickRandom(Collection $ads): ?self
    {
        return $ads->isEmpty() ? null : $ads->random();
    }

    /** Status tampilan untuk CMS: live, scheduled, off_hours, ended, paused, expired. */
    public function displayState(?CarbonInterface $now = null): string
    {
        $now ??= now();

        if ($this->status === self::STATUS_PAUSED) {
            return 'paused';
        }
        if ($this->status === self::STATUS_EXPIRED) {
            return 'expired';
        }
        if ($this->end_at && $this->end_at->lte($now)) {
            return 'ended';
        }
        if ($this->start_at && $this->start_at->gt($now)) {
            return 'scheduled';
        }
        if (! $this->isWithinActiveHours($now)) {
            return 'off_hours';
        }

        return 'live';
    }

    public function isWithinActiveHours(?CarbonInterface $now = null): bool
    {
        if (! $this->active_hours_start || ! $this->active_hours_end) {
            return true;
        }

        $time = ($now ?? now())->format('H:i:s');
        $start = self::normalizeTime($this->active_hours_start);
        $end = self::normalizeTime($this->active_hours_end);

        return $start <= $end
            ? ($start <= $time && $time < $end)
            : ($time >= $start || $time < $end);
    }

    /** "08:00" / "08:00:00" → "08:00:00". */
    public static function normalizeTime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return strlen($value) === 5 ? $value . ':00' : substr($value, 0, 8);
    }

    public function isVideo(): bool
    {
        return $this->placement === self::PLACEMENT_POPUP && $this->type === 'video';
    }

    /** Payload publik (camelCase) untuk Beranda. */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'kicker' => $this->kicker ?: 'SPONSOR RESMI',
            'type' => $this->isVideo() ? 'video' : 'banner',
            'imageUrl' => $this->image_url ?? '',
            'videoUrl' => $this->video_url ?? '',
            'videoDuration' => (int) ($this->video_duration ?: 30),
            'targetUrl' => $this->target_url ?? '',
            'intervalMinutes' => $this->interval_minutes,
            'firstDelaySeconds' => $this->first_delay_seconds,
            'isSkippable' => (bool) ($this->is_skippable ?? true),
            'skipDelaySeconds' => $this->skip_delay_seconds !== null ? (int) $this->skip_delay_seconds : 10,
            'isActive' => true,
        ];
    }

    /** Payload CMS (snake_case) termasuk statistik & status tampilan. */
    public function toCmsArray(): array
    {
        $impressions = (int) $this->impressions;
        $clicks = (int) $this->clicks;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'kicker' => $this->kicker,
            'placement' => $this->placement,
            'type' => $this->type,
            'image_url' => $this->image_url,
            'video_url' => $this->video_url,
            'video_duration' => $this->video_duration,
            'target_url' => $this->target_url,
            'start_at' => $this->start_at?->format('Y-m-d H:i'),
            'end_at' => $this->end_at?->format('Y-m-d H:i'),
            // Dipisah untuk form CMS (date picker + input jam).
            'start_date' => $this->start_at?->format('Y-m-d'),
            'start_time' => $this->start_at?->format('H:i'),
            'end_date' => $this->end_at?->format('Y-m-d'),
            'end_time' => $this->end_at?->format('H:i'),
            'active_hours_start' => $this->active_hours_start ? substr($this->active_hours_start, 0, 5) : null,
            'active_hours_end' => $this->active_hours_end ? substr($this->active_hours_end, 0, 5) : null,
            'status' => $this->status,
            'display_state' => $this->displayState(),
            'order' => (int) $this->sort_order,
            'first_delay_seconds' => $this->first_delay_seconds,
            'interval_minutes' => $this->interval_minutes,
            'is_skippable' => (bool) ($this->is_skippable ?? true),
            'skip_delay_seconds' => $this->skip_delay_seconds !== null ? (int) $this->skip_delay_seconds : 10,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
