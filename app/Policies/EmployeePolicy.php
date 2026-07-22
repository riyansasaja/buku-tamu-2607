<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return false;
    }
}
