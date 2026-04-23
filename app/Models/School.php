<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'package_id',
        'director_id',
        'curriculum',
        'region',
        'district',
        'contact_phone',
        'contact_email',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasFeature(string $feature): bool
    {
        return (bool) ($this->package?->features[$feature] ?? false);
    }
}
