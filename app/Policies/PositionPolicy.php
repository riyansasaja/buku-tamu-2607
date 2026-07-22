<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Position $position): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Position $position): bool
    {
        return false;
    }
}
