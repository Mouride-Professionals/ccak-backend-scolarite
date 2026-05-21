<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExamSessionPolicy extends BasePolicy
{
    protected string $resource = 'exam-sessions';

    public function publish(User $user, Model $session): bool
    {
        return $this->allow($user, 'exam-sessions.update');
    }

    public function close(User $user, Model $session): bool
    {
        return $this->allow($user, 'exam-sessions.update');
    }
}
