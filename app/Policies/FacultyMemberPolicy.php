<?php

namespace App\Policies;

use App\Models\FacultyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FacultyMemberPolicy extends BasePolicy
{
    protected string $resource = 'faculty_members';

    public function view(User $user, Model $facultyMember): bool
    {
        /** @var FacultyMember $facultyMember */
        return $this->canOrOwn($user, 'faculty_members.view', $facultyMember, 'user_id');
    }

    public function update(User $user, Model $facultyMember): bool
    {
        /** @var FacultyMember $facultyMember */
        return $this->canOrOwn($user, 'faculty_members.update', $facultyMember, 'user_id');
    }
}
