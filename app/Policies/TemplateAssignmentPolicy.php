<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\TemplateAssignment;
use App\Models\User;

class TemplateAssignmentPolicy
{
    /**
     * Roles allowed to create a TemplateAssignment (pick a template, assign to class + staff).
     * System Admin is always allowed via early return.
     */
    private const ASSIGNERS = [
        Role::DIRECTOR,
        Role::DEPUTY_DIRECTOR,
        Role::SCHOOL_ADMIN,
        Role::ACADEMIC_HEAD,
        Role::DEPUTY_ACADEMIC_HEAD,
        Role::EXAMINATION_MASTER,
    ];

    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || ($user->isActive() && $user->role !== null);
    }

    public function view(User $user, TemplateAssignment $assignment): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user->school_id === $assignment->school_id;
    }

    public function create(User $user): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user->hasRole(...self::ASSIGNERS);
    }

    public function update(User $user, TemplateAssignment $assignment): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $assignment->school_id) {
            return false;
        }

        if (! $assignment->isEditable()) {
            return false;
        }

        // Creator can always edit; the assignee (e.g. teacher) can fill in data;
        // higher-level assigners can edit too.
        if ($user->id === $assignment->created_by) {
            return true;
        }

        if ($user->id === $assignment->assigned_to_id) {
            return true;
        }

        return $user->hasRole(...self::ASSIGNERS);
    }

    public function markReady(User $user, TemplateAssignment $assignment): bool
    {
        return $this->update($user, $assignment)
            && $assignment->status === TemplateAssignment::STATUS_DRAFT;
    }

    public function reopen(User $user, TemplateAssignment $assignment): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $assignment->school_id) {
            return false;
        }

        if ($assignment->status !== TemplateAssignment::STATUS_READY) {
            return false;
        }

        return $user->id === $assignment->created_by
            || $user->hasRole(...self::ASSIGNERS);
    }

    public function print(User $user, TemplateAssignment $assignment): bool
    {
        // Anyone who can view can print; the act of printing flips to printed.
        return $this->view($user, $assignment);
    }

    public function archive(User $user, TemplateAssignment $assignment): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user->school_id !== $assignment->school_id) {
            return false;
        }

        return $user->id === $assignment->created_by
            || $user->hasRole(Role::DIRECTOR, Role::DEPUTY_DIRECTOR, Role::SCHOOL_ADMIN);
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }
}
