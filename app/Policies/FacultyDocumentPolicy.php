<?php

namespace App\Policies;

use App\Models\FacultyDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FacultyDocumentPolicy extends BasePolicy
{
    protected string $resource = 'faculty_documents';

    public function view(User $user, Model $facultyDocument): bool
    {
        /** @var FacultyDocument $facultyDocument */
        $ownerId = $facultyDocument->facultyMember?->user_id;

        return $this->allow($user, 'faculty_documents.view')
            || ($ownerId && (string) $ownerId === (string) $user->getKey());
    }

    public function update(User $user, Model $facultyDocument): bool
    {
        /** @var FacultyDocument $facultyDocument */
        $ownerId = $facultyDocument->facultyMember?->user_id;

        return $this->allow($user, 'faculty_documents.update')
            || ($ownerId && (string) $ownerId === (string) $user->getKey());
    }

    public function delete(User $user, Model $facultyDocument): bool
    {
        /** @var FacultyDocument $facultyDocument */
        $ownerId = $facultyDocument->facultyMember?->user_id;

        return $this->allow($user, 'faculty_documents.delete')
            || ($ownerId && (string) $ownerId === (string) $user->getKey());
    }
}
