<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\StaffLeave;
use App\Models\User;

class StaffLeavePolicy
{
    /**
     * Roles that can decide on leave requests (approve / reject).
     * HR is the primary approver; leadership can also act.
     */
    public const APPROVERS = [
        Role::HR,
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
        Role::SCHOOL_ADMIN,
    ];

    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $user->hasRole(...StaffProfilePolicy::VIEWERS)
            // Any active user can see their own leaves on the /hr/me page.
            || $user->isActive();
    }

    public function view(User $user, StaffLeave $leave): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $leave->school_id) {
            return false;
        }

        if ($user->id === $leave->user_id) {
            return true;
        }

        return $user->hasRole(...StaffProfilePolicy::VIEWERS);
    }

    /**
     * Any active school user can request leave for themselves. HR can also
     * file a request on behalf of another user (except protected roles).
     */
    public function createFor(User $user, User $subject): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $subject->school_id) {
            return false;
        }

        if ($user->id === $subject->id) {
            return $user->isActive();
        }

        if (! $user->hasRole(Role::HR)) {
            return false;
        }

        return ! in_array($subject->role?->slug, StaffProfilePolicy::PROTECTED_FROM_HR, true);
    }

    public function decide(User $user, StaffLeave $leave): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $leave->school_id) {
            return false;
        }

        if (! $leave->isPending()) {
            return false;
        }

        // Cannot approve your own leave.
        if ($user->id === $leave->user_id) {
            return false;
        }

        if (! $user->hasRole(...self::APPROVERS)) {
            return false;
        }

        // HR cannot decide on Director / Deputy Director / School Admin leaves
        // (those are protected from HR). Director etc. still can.
        if ($user->hasRole(Role::HR)
            && in_array($leave->user?->role?->slug, StaffProfilePolicy::PROTECTED_FROM_HR, true)
        ) {
            return false;
        }

        return true;
    }

    public function cancel(User $user, StaffLeave $leave): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $leave->school_id) {
            return false;
        }

        if (! $leave->isPending()) {
            return false;
        }

        // Requester can cancel their own pending request.
        if ($user->id === $leave->requested_by) {
            return true;
        }

        return $user->hasRole(Role::HR);
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }
}
