<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeliberationSessionPolicy extends BasePolicy
{
    protected string $resource = 'deliberations';

    public function start(User $user, Model $session): bool
    {
        return $this->allow($user, 'deliberations.update');
    }

    public function saveDecision(User $user, Model $session): bool
    {
        return $this->allow($user, 'deliberations.update');
    }

    public function complete(User $user, Model $session): bool
    {
        return $this->allow($user, 'deliberations.update');
    }
}
