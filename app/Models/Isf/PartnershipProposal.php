<?php

namespace App\Models\Isf;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnershipProposal extends Model
{
    use HasFactory;

    protected $table = 'isf_partnership_proposals';

    public const STATUS_BARU = 'baru';
    public const STATUS_DIHUBUNGI = 'dihubungi';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DITOLAK = 'ditolak';

    public const STATUSES = [
        self::STATUS_BARU => 'Baru',
        self::STATUS_DIHUBUNGI => 'Dihubungi',
        self::STATUS_SELESAI => 'Selesai',
        self::STATUS_DITOLAK => 'Ditolak',
    ];

    public const PARTNERSHIP_TYPES = [
        'banner' => 'Display Banner & Slot Iklan',
        'advertorial' => 'Advertorial & Analisis Khusus',
        'event' => 'Event & Roundtable Diskusi',
        'riset' => 'Sponsorship Riset & Policy Brief',
        'lainnya' => 'Kemitraan Khusus / Lainnya',
    ];

    public const TYPES = self::PARTNERSHIP_TYPES;

    protected $fillable = [
        'institution_name',
        'pic_name',
        'email',
        'phone',
        'partnership_type',
        'message',
        'status',
        'internal_notes',
        'contacted_at',
        'contacted_by',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
        ];
    }

    public function contactedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_by');
    }

    public function scopeBaru(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BARU);
    }

    public function scopeDihubungi(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DIHUBUNGI);
    }

    public function scopeSelesai(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SELESAI);
    }

    public function scopeDitolak(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DITOLAK);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function getPartnershipTypeLabelAttribute(): string
    {
        return self::PARTNERSHIP_TYPES[$this->partnership_type] ?? ucfirst($this->partnership_type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}

