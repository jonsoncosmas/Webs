<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Role;
use App\Models\User;

class ExamAttemptPolicy
{
    /**
     * Roles who can enter/manage attempt scores.
     */
    public const SCORERS = [
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
        Role::SCHOOL_ADMIN,
        Role::ACADEMIC_HEAD,
        Role::DEPUTY_ACADEMIC_HEAD,
        Role::EXAMINATION_MASTER,
        Role::TEACHER,
        Role::IT,
    ];

    public function viewAny(User $user): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        // Students / parents use the portal routes (see view() + parent links);
        // staff roles below see school-wide lists.
        return $user->hasRole(...self::SCORERS);
    }

    public function view(User $user, ExamAttempt $attempt): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $attempt->school_id) {
            return false;
        }

        // The student themselves.
        if ($user->id === $attempt->student_user_id) {
            return true;
        }

        // Linked parent.
        if ($user->hasRole(Role::PARENT_ROLE)
            && $user->children()->where('student_user_id', $attempt->student_user_id)->exists()) {
            return true;
        }

        return $user->hasRole(...self::SCORERS);
    }

    public function score(User $user, ExamAttempt $attempt): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $attempt->school_id) {
            return false;
        }

        return $user->hasRole(...self::SCORERS);
    }

    /**
     * A student may *take* a published exam in their own school.
     */
    public function take(User $user, Exam $exam): bool
    {
        if (! $user->hasRole(Role::STUDENT)) {
            return false;
        }

        if ($user->school_id !== $exam->school_id) {
            return false;
        }

        return $exam->status === Exam::STATUS_PUBLISHED;
    }

    /**
     * Submit / save answers on an in-progress attempt — student who owns it.
     */
    public function submit(User $user, ExamAttempt $attempt): bool
    {
        if (! $user->hasRole(Role::STUDENT)) {
            return false;
        }

        return $user->id === $attempt->student_user_id
            && $user->school_id === $attempt->school_id;
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }
}
