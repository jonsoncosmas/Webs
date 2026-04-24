<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Template;
use App\Models\User;

class TemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && ($user->role !== null);
    }

    /**
     * Any active user with a role can view a template definition
     * (schools need to see what's available to assign). System Admin always.
     */
    public function view(User $user, Template $template): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user->isActive() && ($user->role !== null) && $template->is_active;
    }

    public function create(User $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(User $user, Template $template): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(User $user, Template $template): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function archive(User $user, Template $template): bool
    {
        return $this->isSystemAdmin($user);
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->hasRole(Role::SYSTEM_ADMIN);
    }
}
