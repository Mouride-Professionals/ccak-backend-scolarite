<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class KeycloakUserProvider extends EloquentUserProvider
{
    private const DEFAULT_ROLE_ALLOWLIST = ['ADMIN', 'FACULTY', 'STUDENT', 'STAFF'];

    public function retrieveByKeycloakToken(object $token, array $credentials): ?Authenticatable
    {
        $claims = (array) $token;
        $sub = $claims['sub'] ?? null;
        if (! $sub) {
            return null;
        }

        $email = $claims['email'] ?? null;
        $defaultEmail = $email ?: sprintf('%s@keycloak.local', Str::slug($sub));

        /** @var User $user */
        $user = $this->createModel()->newQuery()->firstOrCreate(
            ['keycloak_id' => $sub],
            [
                'email' => $defaultEmail,
                'is_active' => true,
            ]
        );

        $user->last_login_at = now();

        if ($email && $user->email !== $email) {
            $user->email = $email;
        }

        $user->save();

        if (config('keycloak.sync_roles')) {
            $this->syncRoles($user, $claims);
        }

        return $user;
    }

    private function syncRoles(User $user, array $claims): void
    {
        if (! method_exists($user, 'syncRoles')) {
            return;
        }

        $allowedRoles = $this->getAllowedRoles();
        $allowedRolesSet = array_fill_keys($allowedRoles, true);
        $roles = $this->extractAllowedRoles($claims, $allowedRolesSet);
        $guard = $this->resolveGuard($user);

        $existingRoles = Role::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $roles)
            ->pluck('name')
            ->map(fn (string $name): string => strtoupper($name))
            ->unique()
            ->values()
            ->all();

        $missingRoles = array_values(array_diff($roles, $existingRoles));
        if ($missingRoles !== []) {
            Log::warning('Skipping Keycloak role sync for roles missing locally', [
                'user_id' => $user->id,
                'guard' => $guard,
                'missing_roles' => $missingRoles,
            ]);
        }

        $currentRoles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()
            ->map(fn($role) => strtoupper($role))
            ->filter(fn($role) => isset($allowedRolesSet[$role]))
            ->unique()
            ->values()
            ->all()
            : [];

        if ($existingRoles !== $currentRoles) {
            $user->syncRoles($existingRoles);
        }
    }

    private function resolveGuard(User $user): string
    {
        $attributeGuard = $user->getAttribute('guard_name');

        return is_string($attributeGuard) && $attributeGuard !== ''
            ? $attributeGuard
            : (string) config('auth.defaults.guard', 'api');
    }

    private function extractAllowedRoles(array $claims, array $allowedRolesSet): array
    {
        $roles = Arr::wrap(data_get($claims, 'realm_access.roles', []));

        $resourceRoles = collect(data_get($claims, 'resource_access', []))
            ->map(fn($access) => Arr::wrap(data_get($access, 'roles', [])))
            ->flatten()
            ->all();

        return collect(array_merge($roles, $resourceRoles))
            ->filter(fn($role) => is_string($role) && $role !== '')
            ->map(fn($role) => strtoupper($role))
            ->filter(fn($role) => isset($allowedRolesSet[$role]))
            ->unique()
            ->values()
            ->all();
    }

    private function getAllowedRoles(): array
    {
        $roles = config('keycloak.role_allowlist', self::DEFAULT_ROLE_ALLOWLIST);

        return collect(Arr::wrap($roles))
            ->filter(fn($role) => is_string($role) && $role !== '')
            ->map(fn($role) => strtoupper(trim($role)))
            ->unique()
            ->values()
            ->all();
    }
}
