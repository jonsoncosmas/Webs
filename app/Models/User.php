<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DEACTIVATED = 'deactivated';

    protected $fillable = [
        'school_id',
        'role_id',
        'first_name',
        'middle_name',
        'last_name',
        'username',
        'email',
        'phone',
        'password',
        'must_change_password',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name])));
    }

    public function getNameAttribute(): string
    {
        return $this->fullName();
    }

    public function hasRole(string ...$slugs): bool
    {
        return $this->role && in_array($this->role->slug, $slugs, true);
    }

    public function roleLevel(): int
    {
        return $this->role?->level ?? PHP_INT_MAX;
    }

    public function outranks(User $other): bool
    {
        return $this->roleLevel() < $other->roleLevel();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Default password convention: LASTNAME in uppercase.
     */
    public static function defaultPasswordFor(string $lastName): string
    {
        return strtoupper(trim($lastName));
    }

    /**
     * Default username convention: "firstname middlename" lowercased.
     * Falls back to firstname only when middle name is missing.
     */
    public static function defaultUsernameFor(string $firstName, ?string $middleName): string
    {
        $parts = array_filter([$firstName, $middleName], fn ($p) => $p !== null && $p !== '');

        return strtolower(trim(implode(' ', $parts)));
    }
}
