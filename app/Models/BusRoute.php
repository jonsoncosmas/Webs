<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusRoute extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'status',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(BusStop::class)->orderBy('position');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(BusVehicle::class);
    }
}
