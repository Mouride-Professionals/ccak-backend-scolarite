<?php

namespace App\Policies;

use App\Models\GeneratedDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GeneratedDocumentPolicy extends BasePolicy
{
    protected string $resource = 'generated_documents';

    private function isOwnerOrGenerator(User $user, Model $document): bool
    {
        /** @var GeneratedDocument $document */
        return $this->ownsViaRelation($user, $document, 'student', 'user_id')
            || $this->owns($user, $document, 'generated_by');
    }

    public function view(User $user, Model $document): bool
    {
        return $this->allow($user, 'generated_documents.view')
            || $this->allow($user, 'documents.view')
            || $this->isOwnerOrGenerator($user, $document);
    }

    public function update(User $user, Model $document): bool
    {
        return $this->allow($user, 'generated_documents.update')
            || $this->allow($user, 'documents.update');
    }

    public function delete(User $user, Model $document): bool
    {
        return $this->allow($user, 'generated_documents.delete')
            || $this->allow($user, 'documents.delete');
    }
}
