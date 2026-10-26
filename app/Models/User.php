<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * ISF CMS feature keys (one per content module). Used by the ACL page,
     * the `feature:<key>` middleware and the CMS sidebar.
     */
    public const ISF_FEATURES = [
        'banner',
        'artikel',
        'infografik',
        'tokoh',
        'isu',
        'data_grafik',
        'alert',
        'email_briefing',
        'pengaturan_halaman',
    ];

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
     * Features granted when allowed_features is NULL (NULL = all features).
     *
     * @return list<string>
     */
    public static function defaultFeatures(): array
    {
        return self::ISF_FEATURES;
    }

    /**
     * Features this user can use (super-admin always has all of them).
     *
     * @return list<string>
     */
    public function features(): array
    {
        if ($this->hasRole('super-admin')) {
            return self::ISF_FEATURES;
        }

        $features = $this->allowed_features;

        return is_array($features) ? array_values($features) : self::defaultFeatures();
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
            $features = self::defaultFeatures();
        }

        return in_array($feature, $features);
    }
}
