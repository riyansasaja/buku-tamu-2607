<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkUnit;

class WorkUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, WorkUnit $workUnit): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, WorkUnit $workUnit): bool
    {
        return false;
    }
}
