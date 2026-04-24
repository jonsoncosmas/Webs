<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\StaffCertificate;
use App\Models\User;

class StaffCertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $user->hasRole(...StaffProfilePolicy::VIEWERS);
    }

    public function view(User $user, StaffCertificate $certificate): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $certificate->school_id) {
            return false;
        }

        if ($user->id === $certificate->user_id) {
            return true;
        }

        return $user->hasRole(...StaffProfilePolicy::VIEWERS);
    }

    /**
     * Can $user add a certificate to $subject's record.
     */
    public function createFor(User $user, User $subject): bool
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

        return ! in_array($subject->role?->slug, StaffProfilePolicy::PROTECTED_FROM_HR, true);
    }

    public function archive(User $user, StaffCertificate $certificate): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $certificate->school_id) {
            return false;
        }

        return $user->hasRole(Role::HR)
            && ! in_array($certificate->user?->role?->slug, StaffProfilePolicy::PROTECTED_FROM_HR, true);
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }
}
