<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Relative to the public disk (storage/app/public). Web URL: /storage/{PROFILE_IMAGE_PATH}/{filename}.
     * users.profile_image stores only the filename under this directory.
     */
    public const PROFILE_IMAGE_PATH = 'images/profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'is_active',
        'profile_image',
        'department_id',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'full_name',
        'profile_image_url',
        'permission_names',
        'role_names',
    ];

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Permission names for the current user (for frontend permission checks).
     *
     * @return array<int, string>
     */
    public function getPermissionNamesAttribute(): array
    {
        return $this->getAllPermissions()->pluck('name')->values()->all();
    }

    /**
     * Role names for the current user (for frontend role checks).
     *
     * @return array<int, string>
     */
    public function getRoleNamesAttribute(): array
    {
        return $this->getRoleNames()->values()->all();
    }

    /**
     * Get the department the user belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<Import, $this>
     */
    public function imports()
    {
        return $this->hasMany(Import::class);
    }

    /**
     * Get the URL for the user's profile image (absolute so it works from SPA on any origin).
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if (empty($this->profile_image)) {
            return null;
        }

        return asset('storage/'.self::PROFILE_IMAGE_PATH.'/'.$this->profile_image);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Whether the user may sign in and use the application.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
