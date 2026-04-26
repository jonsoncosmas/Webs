<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\School;
use App\Models\User;

class AnalyticsPolicy
{
    /**
     * Roles allowed to see analytics dashboards. Per spec:
     *  - Director / Deputy Director: full school visibility.
     *  - School Admin: full school visibility.
     *  - Academic Head / Deputy Academic Head: academic + discipline correlation.
     *  - HR: staff/HR-side KPIs (the rest of the page still renders, just gated by view).
     *  - Discipline Head / Deputy: discipline-side KPIs.
     */
    public const VIEWERS = [
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
        Role::SCHOOL_ADMIN,
        Role::ACADEMIC_HEAD,
        Role::DEPUTY_ACADEMIC_HEAD,
        Role::HR,
        Role::DISCIPLINE_HEAD,
        Role::DEPUTY_DISCIPLINE_HEAD,
    ];

    public function viewSchool(User $user, School $school): bool
    {
        if ($user->hasRole(Role::SYSTEM_ADMIN)) {
            return true;
        }

        if ($user->school_id !== $school->id) {
            return false;
        }

        return $user->hasRole(...self::VIEWERS);
    }

    public function viewStudent(User $user, User $student): bool
    {
        if (! $student->hasRole(Role::STUDENT)) {
            return false;
        }

        if ($user->hasRole(Role::SYSTEM_ADMIN)) {
            return true;
        }

        if ($user->school_id !== $student->school_id) {
            return false;
        }

        return $user->hasRole(...self::VIEWERS);
    }
}
