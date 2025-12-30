<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        Log::channel('audit')->info('User created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'by_user' => auth()->id() ?? 'system',
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $changes = $user->getChanges();

        // Log sensitive changes
        if (array_key_exists('is_active', $changes)) {
            Log::channel('security')->warning('User status changed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'from' => $user->getOriginal('is_active'),
                'to' => $user->is_active,
                'by_user' => auth()->id(),
            ]);
        }

        if (array_key_exists('password', $changes)) {
            Log::channel('security')->info('Password changed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'by_user' => auth()->id() ?? $user->id,
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        Log::channel('audit')->warning('User deleted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'by_user' => auth()->id(),
            'deleted_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        Log::channel('audit')->info('User restored', [
            'user_id' => $user->id,
            'email' => $user->email,
            'by_user' => auth()->id(),
        ]);
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        Log::channel('security')->alert('User permanently deleted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'by_user' => auth()->id(),
        ]);
    }
}
