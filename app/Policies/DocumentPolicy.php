<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DocumentPolicy extends BasePolicy
{
    protected string $resource = 'documents';

    public function view(User $user, Model $document): bool
    {
        return $this->allow($user, 'documents.view')
            || $this->ownsViaRelation($user, $document, 'student', 'user_id');
    }

    public function update(User $user, Model $document): bool
    {
        return $this->allow($user, 'documents.update')
            || $this->ownsViaRelation($user, $document, 'student', 'user_id');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allow($user, 'documents.delete')
            || $this->ownsViaRelation($user, $model, 'student', 'user_id');
    }

    public function download(User $user, Document $document): bool
    {
        return $this->allow($user, 'documents.download')
            || $this->ownsViaRelation($user, $document, 'student', 'user_id');
    }

    public function review(User $user, Document $document): bool
    {
        return $this->allow($user, 'documents.review');
    }
}
