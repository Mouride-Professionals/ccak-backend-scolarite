<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GuardianPolicy extends BasePolicy
{
    protected string $resource = 'guardians';

    public function view(User $user, Model $guardian): bool
    {
        return $this->allow($user, 'guardians.view')
            || $this->ownsViaRelation($user, $guardian, 'student', 'user_id');
    }

    public function update(User $user, Model $guardian): bool
    {
        return $this->allow($user, 'guardians.update')
            || $this->ownsViaRelation($user, $guardian, 'student', 'user_id');
    }

    public function delete(User $user, Model $guardian): bool
    {
        return $this->allow($user, 'guardians.delete')
            || $this->ownsViaRelation($user, $guardian, 'student', 'user_id');
    }
}
