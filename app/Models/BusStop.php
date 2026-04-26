<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusStop extends Model
{
    protected $fillable = [
        'bus_route_id',
        'name',
        'latitude',
        'longitude',
        'position',
        'scheduled_pickup',
        'scheduled_dropoff',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'position' => 'integer',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(BusRoute::class, 'bus_route_id');
    }
}
