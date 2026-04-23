<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\Role;
use App\Models\User;

class ExamPolicy
{
    /**
     * Roles allowed to generate exams.
     * (System Admin is always allowed via an early-return in each method.)
     */
    private const CREATORS = [
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
        Role::SCHOOL_ADMIN,
        Role::ACADEMIC_HEAD,
        Role::DEPUTY_ACADEMIC_HEAD,
        Role::EXAMINATION_MASTER,
        Role::IT,
        Role::TEACHER,
    ];

    /**
     * Whose role is *below* which other's when editing/approving/overriding?
     * Role level is LOWER = MORE authority (e.g. Director=10, School Admin=30).
     */
    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || ($user->isActive() && $user->role !== null);
    }

    public function view(User $user, Exam $exam): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user->school_id === $exam->school_id;
    }

    public function create(User $user): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user->hasRole(...self::CREATORS);
    }

    /**
     * Editable only by creator (while editable) OR by anyone who can override.
     */
    public function update(User $user, Exam $exam): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if (! $exam->isEditable()) {
            return false;
        }

        if ($exam->creator_id === $user->id) {
            return true;
        }

        return $this->override($user, $exam);
    }

    /**
     * Submit for approval (creator only).
     */
    public function submit(User $user, Exam $exam): bool
    {
        return $exam->creator_id === $user->id
            && $exam->status === Exam::STATUS_DRAFT;
    }

    /**
     * Approve a submitted exam. The approver must strictly outrank the creator.
     *   - Director's exams (locked_to_id set) are auto-approved at creation;
     *     no one else can approve them.
     *   - Same-level roles cannot approve each other.
     */
    public function approve(User $user, Exam $exam): bool
    {
        if ($exam->status !== Exam::STATUS_PENDING) {
            return false;
        }

        if ($exam->isLocked()) {
            return false; // Director exams don't go through approval.
        }

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->id === $exam->creator_id) {
            return false;
        }

        if ($user->school_id !== $exam->school_id) {
            return false;
        }

        return $user->roleLevel() < $exam->creator_role_level;
    }

    public function reject(User $user, Exam $exam): bool
    {
        return $this->approve($user, $exam);
    }

    /**
     * Override an exam (take control away from creator).
     *   - Higher role can override lower.
     *   - Same role CANNOT override each other.
     *   - Director-locked exams can only be overridden by the Director themselves
     *     (or System Admin).
     *   - Not allowed on published/archived exams.
     */
    public function override(User $user, Exam $exam): bool
    {
        if (in_array($exam->status, [Exam::STATUS_PUBLISHED, Exam::STATUS_ARCHIVED], true)) {
            return false;
        }

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $exam->school_id) {
            return false;
        }

        if ($exam->isLocked()) {
            return $user->id === $exam->locked_to_id;
        }

        if ($user->id === $exam->creator_id) {
            return false; // can't "override" your own exam
        }

        return $user->roleLevel() < $exam->creator_role_level;
    }

    /**
     * Publish an approved exam. Creator or anyone who can override may publish.
     */
    public function publish(User $user, Exam $exam): bool
    {
        if ($exam->status !== Exam::STATUS_APPROVED) {
            return false;
        }

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $exam->school_id) {
            return false;
        }

        if ($exam->isLocked() && $user->id !== $exam->locked_to_id) {
            return false;
        }

        return $user->id === $exam->creator_id
            || $user->roleLevel() < $exam->creator_role_level;
    }

    public function archive(User $user, Exam $exam): bool
    {
        if ($exam->status === Exam::STATUS_ARCHIVED) {
            return false;
        }

        return $this->override($user, $exam) || $user->id === $exam->creator_id;
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }
}
