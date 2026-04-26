<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\Role;
use App\Models\School;
use App\Models\User;

class PackagePolicy
{
    /**
     * Only System Admin can manage the package catalog or assign packages to schools.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }

    public function view(User $user, Package $package): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }

    public function manage(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }

    public function assignToSchool(User $user, School $school): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }

    /**
     * School-side viewers of their own subscription card.
     */
    public function viewSchoolSubscription(User $user, School $school): bool
    {
        if ($user->hasRole(Role::SYSTEM_ADMIN)) {
            return true;
        }

        if ($user->school_id !== $school->id) {
            return false;
        }

        return $user->hasRole(
            Role::DIRECTOR,
            Role::DEPUTY_DIRECTOR,
            Role::SCHOOL_ADMIN,
        );
    }
}
