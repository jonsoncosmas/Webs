<?php

namespace App\Services\Bus;

use App\Exceptions\BusFieldException;
use App\Models\BusRoute;
use App\Models\BusStop;
use App\Models\BusVehicle;
use App\Models\School;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class BusTrackingService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createRoute(User $actor, School $school, array $attributes): BusRoute
    {
        $route = BusRoute::create([
            'school_id' => $school->id,
            'name' => $attributes['name'],
            'code' => $attributes['code'] ?? null,
            'description' => $attributes['description'] ?? null,
            'status' => BusRoute::STATUS_ACTIVE,
        ]);

        $this->logger->log('bus.route.created', $route, [
            'school_id' => $school->id,
            'name' => $route->name,
        ]);

        return $route;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addStop(User $actor, BusRoute $route, array $attributes): BusStop
    {
        $position = $attributes['position'] ?? ($route->stops()->max('position') + 1);
        $stop = BusStop::create([
            'bus_route_id' => $route->id,
            'name' => $attributes['name'],
            'latitude' => $attributes['latitude'] ?? null,
            'longitude' => $attributes['longitude'] ?? null,
            'position' => $position,
            'scheduled_pickup' => $attributes['scheduled_pickup'] ?? null,
            'scheduled_dropoff' => $attributes['scheduled_dropoff'] ?? null,
        ]);

        $this->logger->log('bus.stop.added', $stop, [
            'bus_route_id' => $route->id,
            'name' => $stop->name,
        ]);

        return $stop;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createVehicle(User $actor, School $school, array $attributes): BusVehicle
    {
        $routeId = $attributes['bus_route_id'] ?? null;
        if ($routeId !== null) {
            $route = BusRoute::find($routeId);
            if (! $route || $route->school_id !== $school->id) {
                throw new BusFieldException('bus_route_id', 'Route does not belong to this school.');
            }
        }

        $driverId = $attributes['driver_user_id'] ?? null;
        if ($driverId !== null) {
            $driver = User::find($driverId);
            if (! $driver || $driver->school_id !== $school->id) {
                throw new BusFieldException('driver_user_id', 'Driver does not belong to this school.');
            }
        }

        $vehicle = BusVehicle::create([
            'school_id' => $school->id,
            'bus_route_id' => $routeId,
            'driver_user_id' => $driverId,
            'plate_number' => $attributes['plate_number'],
            'label' => $attributes['label'] ?? null,
            'capacity' => $attributes['capacity'] ?? 0,
            'status' => $attributes['status'] ?? BusVehicle::STATUS_ACTIVE,
        ]);

        $this->logger->log('bus.vehicle.created', $vehicle, [
            'school_id' => $school->id,
            'plate_number' => $vehicle->plate_number,
        ]);

        return $vehicle;
    }

    public function recordPosition(User $actor, BusVehicle $vehicle, float $lat, float $lng): BusVehicle
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new InvalidArgumentException('Coordinates out of range.');
        }

        $vehicle->update([
            'last_latitude' => $lat,
            'last_longitude' => $lng,
            'last_position_at' => Carbon::now(),
        ]);

        $this->logger->log('bus.vehicle.position_recorded', $vehicle, [
            'lat' => $lat,
            'lng' => $lng,
        ]);

        return $vehicle->fresh();
    }
}
