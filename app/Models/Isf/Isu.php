<?php

namespace App\Models\Isf;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isu Mingguan — "Anatomi Isu Mingguan" (7 frameworks).
 */
class Isu extends Model
{
    use HasPublishStatus;

    protected $table = 'isf_isus';

    protected $fillable = [
        'slug',
        'title',
        'category',
        'published_at',
        'summary',
        'author',
        'teaser',
        'is_weekly',
        'ringkasan',
        'visual_chart_id',
        'visual_image_alt',
        'visual_image_url',
        'fakta_kunci',
        'linimasa',
        'aktor',
        'grc_governance',
        'grc_risk_level',
        'grc_risk_description',
        'grc_compliance',
        'dampak_sektoral',
        'dampak_reaksi',
        'selanjutnya_tahapan',
        'selanjutnya_pantau',
        'metodologi_text',
        'metodologi_signals',
        'faq',
        'related',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date:Y-m-d',
            'is_weekly' => 'boolean',
            'visual_chart_id' => 'integer',
            'fakta_kunci' => 'array',
            'linimasa' => 'array',
            'aktor' => 'array',
            'grc_compliance' => 'array',
            'dampak_sektoral' => 'array',
            'dampak_reaksi' => 'array',
            'selanjutnya_tahapan' => 'array',
            'selanjutnya_pantau' => 'array',
            'metodologi_signals' => 'array',
            'faq' => 'array',
            'related' => 'array',
        ];
    }

    /**
     * The published "Isu Mingguan" featured this week (latest flagged one).
     */
    public static function currentWeekly(): ?self
    {
        return static::query()->terbit()->where('is_weekly', true)
            ->orderByDesc('published_at')->orderByDesc('id')->first();
    }

    /**
     * "02 Visual Kunci" chart (Data Grafik).
     */
    public function visualChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'visual_chart_id');
    }
}
