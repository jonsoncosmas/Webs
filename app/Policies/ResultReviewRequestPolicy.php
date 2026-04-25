<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\ResultReviewRequest;
use App\Models\Role;
use App\Models\User;

class ResultReviewRequestPolicy
{
    /**
     * Roles who staff the Academic Head review inbox.
     */
    public const DECIDERS = [
        Role::ACADEMIC_HEAD,
        Role::DEPUTY_ACADEMIC_HEAD,
        Role::SCHOOL_ADMIN,
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
    ];

    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user) || $user->hasRole(...self::DECIDERS);
    }

    public function view(User $user, ResultReviewRequest $request): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $request->school_id) {
            return false;
        }

        if ($user->id === $request->student_user_id || $user->id === $request->submitted_by) {
            return true;
        }

        if ($user->hasRole(Role::PARENT_ROLE)
            && $user->children()->where('student_user_id', $request->student_user_id)->exists()) {
            return true;
        }

        return $user->hasRole(...self::DECIDERS);
    }

    /**
     * Students can submit reviews on their own attempts; linked parents can too.
     */
    public function create(User $user, ExamAttempt $attempt): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $attempt->school_id) {
            return false;
        }

        if ($user->id === $attempt->student_user_id) {
            return true;
        }

        if ($user->hasRole(Role::PARENT_ROLE)
            && $user->children()->where('student_user_id', $attempt->student_user_id)->exists()) {
            return true;
        }

        return false;
    }

    public function decide(User $user, ResultReviewRequest $request): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $request->school_id) {
            return false;
        }

        if (! $request->isOpen()) {
            return false;
        }

        return $user->hasRole(...self::DECIDERS);
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }
}
