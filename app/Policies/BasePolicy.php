<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\PermissionService;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    protected string $resource = '';

    public function __construct(protected PermissionService $permissions)
    {
    }

    protected function allow(User $user, string $permission): bool
    {
        return $this->permissions->can($user, $permission);
    }

    public function viewAny(User $user): bool
    {
        return $this->allow($user, "{$this->resource}.view");
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allow($user, "{$this->resource}.view");
    }

    public function create(User $user): bool
    {
        return $this->allow($user, "{$this->resource}.create");
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allow($user, "{$this->resource}.update");
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allow($user, "{$this->resource}.delete");
    }

    protected function owns(User $user, Model $model, string $ownerKey = 'user_id'): bool
    {
        return $this->permissions->owns($user, $model, $ownerKey);
    }

    protected function canOrOwn(User $user, string $permission, Model $model, string $ownerKey = 'user_id'): bool
    {
        return $this->permissions->canOrOwn($user, $permission, $model, $ownerKey);
    }

    /**
     * Check ownership through a relationship (e.g., document->student->user_id).
     */
    protected function ownsViaRelation(User $user, Model $model, string $relation, string $ownerKey = 'user_id'): bool
    {
        $related = $model->{$relation};

        if (! $related) {
            return false;
        }

        return (string) data_get($related, $ownerKey) === (string) $user->getKey();
    }
}
