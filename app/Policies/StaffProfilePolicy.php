<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;

class StaffProfilePolicy
{
    /**
     * Roles HR is forbidden from administering (per spec:
     * "HR: full control except Director and School Admin").
     * Deputy Director is treated as Director-adjacent here.
     */
    public const PROTECTED_FROM_HR = [
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
        Role::SCHOOL_ADMIN,
        Role::SYSTEM_ADMIN,
    ];

    /**
     * Roles who can browse the staff directory (read-only if not HR).
     */
    public const VIEWERS = [
        Role::HR,
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
        Role::SCHOOL_ADMIN,
    ];

    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user) || $user->hasRole(...self::VIEWERS);
    }

    /**
     * Can $user look at $subject's staff profile.
     * $subject here is the profile owner (via StaffProfile->user).
     */
    public function view(User $user, StaffProfile $profile): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $profile->school_id) {
            return false;
        }

        // Self-view.
        if ($user->id === $profile->user_id) {
            return true;
        }

        return $user->hasRole(...self::VIEWERS);
    }

    public function viewUser(User $user, User $subject): bool
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

    /**
     * HR is the only school role that can edit a staff profile,
     * and only if the target isn't one of the protected roles.
     */
    public function updateFor(User $user, User $subject): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $subject->school_id) {
            return false;
        }

        if (! $user->hasRole(Role::HR)) {
            return false;
        }

        return ! $this->isProtectedFromHr($subject);
    }

    public function update(User $user, StaffProfile $profile): bool
    {
        return $this->updateFor($user, $profile->user);
    }

    public function create(User $user): bool
    {
        return $this->isSystemAdmin($user) || $user->hasRole(Role::HR);
    }

    /**
     * Suspend/deactivate a user's account. HR can act on non-protected roles;
     * Director / Deputy Director / School Admin can act on everyone below them.
     */
    public function suspend(User $user, User $subject): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $subject->school_id) {
            return false;
        }

        // Never let someone suspend themselves through this policy.
        if ($user->id === $subject->id) {
            return false;
        }

        if ($user->hasRole(Role::HR)) {
            return ! $this->isProtectedFromHr($subject);
        }

        if ($user->hasRole(Role::DIRECTOR, Role::DEPUTY_DIRECTOR, Role::SCHOOL_ADMIN)) {
            return $user->outranks($subject);
        }

        return false;
    }

    public function activate(User $user, User $subject): bool
    {
        return $this->suspend($user, $subject);
    }

    public function isProtectedFromHr(User $subject): bool
    {
        return $subject->role !== null && in_array($subject->role->slug, self::PROTECTED_FROM_HR, true);
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }
}
