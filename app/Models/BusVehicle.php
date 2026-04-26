<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusVehicle extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'school_id',
        'bus_route_id',
        'driver_user_id',
        'plate_number',
        'label',
        'capacity',
        'status',
        'last_latitude',
        'last_longitude',
        'last_position_at',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'last_latitude' => 'float',
        'last_longitude' => 'float',
        'last_position_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(BusRoute::class, 'bus_route_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function hasKnownPosition(): bool
    {
        return $this->last_latitude !== null && $this->last_longitude !== null;
    }
}
