<?php

namespace App\Policies;

use App\Models\DisciplineIncident;
use App\Models\Role;
use App\Models\User;

class DisciplineIncidentPolicy
{
    /**
     * Roles that can browse the incident directory and view any school incident.
     */
    public const VIEWERS = [
        Role::DISCIPLINE_HEAD,
        Role::DEPUTY_DISCIPLINE_HEAD,
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
        Role::SCHOOL_ADMIN,
        Role::ACADEMIC_HEAD,
        Role::DEPUTY_ACADEMIC_HEAD,
        Role::HR,
    ];

    /**
     * Roles that can decide (resolve / dismiss) an incident.
     */
    public const DECIDERS = [
        Role::DISCIPLINE_HEAD,
        Role::DEPUTY_DISCIPLINE_HEAD,
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
        Role::SCHOOL_ADMIN,
    ];

    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user) || $user->hasRole(...self::VIEWERS);
    }

    public function view(User $user, DisciplineIncident $incident): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $incident->school_id) {
            return false;
        }

        // Subject can always see their own incidents.
        if ($user->id === $incident->subject_id) {
            return true;
        }

        // Reporter can see what they reported.
        if ($user->id === $incident->reported_by) {
            return true;
        }

        return $user->hasRole(...self::VIEWERS);
    }

    /**
     * Any active school user can *log* an incident on another user in the same school.
     * Students and parents cannot report incidents (per spec).
     */
    public function create(User $user): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if (! $user->isActive() || $user->school_id === null) {
            return false;
        }

        return ! $user->hasRole(Role::STUDENT, Role::PARENT_ROLE);
    }

    public function decide(User $user, DisciplineIncident $incident): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $incident->school_id) {
            return false;
        }

        if (! $incident->isOpen()) {
            return false;
        }

        // You can't decide on your own incident (avoid self-exoneration).
        if ($user->id === $incident->subject_id) {
            return false;
        }

        return $user->hasRole(...self::DECIDERS);
    }

    /**
     * Reporter can cancel their own open report within its lifecycle; deciders can too.
     */
    public function cancel(User $user, DisciplineIncident $incident): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $incident->school_id) {
            return false;
        }

        if (! $incident->isOpen()) {
            return false;
        }

        if ($user->id === $incident->reported_by) {
            return true;
        }

        return $user->hasRole(...self::DECIDERS);
    }

    /**
     * Can $user see $subject's timeline (incidents + overlay).
     */
    public function viewTimeline(User $user, User $subject): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $subject->school_id) {
            return false;
        }

        if ($user->id === $subject->id) {
            return true;
        }

        return $user->hasRole(...self::VIEWERS);
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }
}
