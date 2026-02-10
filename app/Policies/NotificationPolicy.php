<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotificationPolicy extends BasePolicy
{
    protected string $resource = 'notifications';

    public function view(User $user, Model $notification): bool
    {
        /** @var Notification $notification */
        return $this->canOrOwn($user, 'notifications.view', $notification, 'user_id');
    }

    public function update(User $user, Model $notification): bool
    {
        /** @var Notification $notification */
        return $this->canOrOwn($user, 'notifications.update', $notification, 'user_id');
    }

    public function delete(User $user, Model $notification): bool
    {
        /** @var Notification $notification */
        return $this->canOrOwn($user, 'notifications.delete', $notification, 'user_id');
    }
}
