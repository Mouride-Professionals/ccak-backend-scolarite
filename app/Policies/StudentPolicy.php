<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StudentPolicy extends BasePolicy
{
    protected string $resource = 'students';

    public function view(User $user, Model $student): bool
    {
        return $this->canOrOwn($user, 'students.view', $student, 'user_id');
    }

    public function update(User $user, Model $student): bool
    {
        return $this->canOrOwn($user, 'students.update', $student, 'user_id');
    }

    public function updateStatus(User $user, Model $student): bool
    {
        return $this->canOrOwn($user, 'students.update', $student, 'user_id');
    }

    public function viewGrades(User $user, Model $student): bool
    {
        return $this->allow($user, 'grades.view')
            || $this->owns($user, $student, 'user_id');
    }
}
