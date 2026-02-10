<?php

namespace App\Policies;

use App\Models\FacultyContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FacultyContractPolicy extends BasePolicy
{
    protected string $resource = 'faculty_contracts';

    public function view(User $user, Model $facultyContract): bool
    {
        /** @var FacultyContract $facultyContract */
        $ownerId = $facultyContract->facultyMember?->user_id;

        return $this->allow($user, 'faculty_contracts.view')
            || ($ownerId && (string) $ownerId === (string) $user->getKey());
    }

    public function update(User $user, Model $facultyContract): bool
    {
        /** @var FacultyContract $facultyContract */
        $ownerId = $facultyContract->facultyMember?->user_id;

        return $this->allow($user, 'faculty_contracts.update')
            || ($ownerId && (string) $ownerId === (string) $user->getKey());
    }

    public function delete(User $user, Model $facultyContract): bool
    {
        /** @var FacultyContract $facultyContract */
        $ownerId = $facultyContract->facultyMember?->user_id;

        return $this->allow($user, 'faculty_contracts.delete')
            || ($ownerId && (string) $ownerId === (string) $user->getKey());
    }
}
