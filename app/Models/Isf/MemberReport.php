<?php

namespace App\Models\Isf;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model MemberReport — Laporan / Pengaduan Kendala & Masukan dari Anggota ISF.
 */
class MemberReport extends Model
{
    use HasFactory;

    protected $table = 'isf_member_reports';

    protected $fillable = [
        'user_id',
        'category',
        'subject',
        'message',
        'attachment_path',
        'status',
        'admin_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function getCategoryLabelAttribute(): string
    {
        $map = [
            'teknis' => 'Kendala Teknis / Bug',
            'konten' => 'Laporan Konten / Koreksi',
            'akun' => 'Masalah Akun / Login',
            'kebijakan' => 'Kritik & Masukan Kebijakan',
            'lainnya' => 'Lainnya',
        ];

        return $map[$this->category] ?? ucfirst($this->category);
    }
}
