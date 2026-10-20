<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'allowed_features',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'allowed_features' => 'array',
        ];
    }

    /**
     * Check if the user has access to a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $features = $this->allowed_features;
        if (!is_array($features)) {
            $features = [
                "undangan_digital",
                "rsvp_online",
                "informasi_acara",
                "love_story",
                "manajemen_tamu",
                "amplop_digital",
                "galeri",
                "countdown",
                "qr_checkin",
                "live_streaming",
                "ucapan_doa",
                "wishlist"
            ];
        }

        return in_array($feature, $features);
    }

    /**
     * Get the articles created by this user.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Get the cards created by this user.
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    /**
     * Get the weddings created by this user.
     */
    public function weddings(): HasMany
    {
        return $this->hasMany(Wedding::class);
    }
}
