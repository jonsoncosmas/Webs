<?php

namespace App\Policies;

use App\Models\BusRoute;
use App\Models\BusVehicle;
use App\Models\Role;
use App\Models\School;
use App\Models\User;

class BusPolicy
{
    /**
     * Roles that can manage bus routes / vehicles for a school. Bus tracking is an
     * Elite-only feature — non-Elite schools are blocked even for these roles.
     */
    public const MANAGERS = [
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
        Role::SCHOOL_ADMIN,
        Role::IT,
    ];

    public function viewAny(User $user): bool
    {
        if ($user->hasRole(Role::SYSTEM_ADMIN)) {
            return true;
        }

        $school = $user->school;
        if (! $school || ! $school->hasFeature('bus_tracking')) {
            return false;
        }

        return $user->hasRole(...self::MANAGERS);
    }

    public function manageSchool(User $user, School $school): bool
    {
        if ($user->hasRole(Role::SYSTEM_ADMIN)) {
            return true;
        }

        if ($user->school_id !== $school->id) {
            return false;
        }

        if (! $school->hasFeature('bus_tracking')) {
            return false;
        }

        return $user->hasRole(...self::MANAGERS);
    }

    public function viewRoute(User $user, BusRoute $route): bool
    {
        return $this->manageSchool($user, $route->school);
    }

    public function updateRoute(User $user, BusRoute $route): bool
    {
        return $this->manageSchool($user, $route->school);
    }

    public function viewVehicle(User $user, BusVehicle $vehicle): bool
    {
        return $this->manageSchool($user, $vehicle->school);
    }

    public function updateVehicle(User $user, BusVehicle $vehicle): bool
    {
        return $this->manageSchool($user, $vehicle->school);
    }
}
