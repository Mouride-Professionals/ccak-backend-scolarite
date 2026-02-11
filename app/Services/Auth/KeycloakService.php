<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KeycloakService
{
    public function isConfigured(): bool
    {
        return (bool) config('keycloak.admin_api_enabled', false)
            && (string) config('keycloak.server_url') !== ''
            && (string) config('keycloak.realm') !== '';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createStudentUser(array $data): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $token = $this->getAdminToken();
        if (! $token) {
            return null;
        }

        $response = Http::timeout(15)
            ->withToken($token)
            ->acceptJson()
            ->post($this->adminUsersEndpoint(), [
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'firstName' => $data['first_name'] ?? null,
                'lastName' => $data['last_name'] ?? null,
                'enabled' => true,
                'emailVerified' => false,
                'credentials' => [[
                    'type' => 'password',
                    'value' => $data['temporary_password'] ?? config('keycloak.default_temporary_password', 'ChangeMe123!'),
                    'temporary' => true,
                ]],
            ]);

        if (! $response->successful()) {
            Log::warning('Keycloak user creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $location = (string) $response->header('Location');
        $keycloakUserId = $location !== '' ? basename($location) : null;

        if (! $keycloakUserId) {
            Log::warning('Keycloak user created but Location header missing');

            return null;
        }

        $this->assignRealmRole($keycloakUserId, 'STUDENT');

        return $keycloakUserId;
    }

    public function assignRealmRole(string $userId, string $roleName): bool
    {
        $token = $this->getAdminToken();
        if (! $token) {
            return false;
        }

        $roleResponse = Http::timeout(15)
            ->withToken($token)
            ->acceptJson()
            ->get($this->adminBaseUrl() . '/roles/' . $roleName);

        if (! $roleResponse->successful()) {
            Log::warning('Keycloak role lookup failed', [
                'role' => $roleName,
                'status' => $roleResponse->status(),
                'body' => $roleResponse->body(),
            ]);

            return false;
        }

        $rolePayload = $roleResponse->json();

        $assignResponse = Http::timeout(15)
            ->withToken($token)
            ->acceptJson()
            ->post($this->adminBaseUrl() . "/users/{$userId}/role-mappings/realm", [$rolePayload]);

        if (! $assignResponse->successful()) {
            Log::warning('Keycloak role assignment failed', [
                'user_id' => $userId,
                'role' => $roleName,
                'status' => $assignResponse->status(),
                'body' => $assignResponse->body(),
            ]);

            return false;
        }

        return true;
    }

    private function adminUsersEndpoint(): string
    {
        return $this->adminBaseUrl() . '/users';
    }

    private function adminBaseUrl(): string
    {
        $baseUrl = rtrim((string) config('keycloak.server_url'), '/');
        $realm = (string) config('keycloak.realm');

        return "{$baseUrl}/admin/realms/{$realm}";
    }

    private function tokenEndpoint(): string
    {
        $baseUrl = rtrim((string) config('keycloak.server_url'), '/');
        $realm = (string) config('keycloak.realm');

        return "{$baseUrl}/realms/{$realm}/protocol/openid-connect/token";
    }

    private function getAdminToken(): ?string
    {
        $adminClientId = (string) config('keycloak.admin_client_id', 'admin-cli');
        $adminClientSecret = (string) config('keycloak.admin_client_secret', '');
        $adminUsername = (string) config('keycloak.admin_username', '');
        $adminPassword = (string) config('keycloak.admin_password', '');

        $payload = [
            'client_id' => $adminClientId,
        ];

        if ($adminClientSecret !== '') {
            $payload['grant_type'] = 'client_credentials';
            $payload['client_secret'] = $adminClientSecret;
        } else {
            $payload['grant_type'] = 'password';
            $payload['username'] = $adminUsername;
            $payload['password'] = $adminPassword;
        }

        $response = Http::asForm()
            ->timeout(15)
            ->acceptJson()
            ->post($this->tokenEndpoint(), $payload);

        if (! $response->successful()) {
            Log::warning('Keycloak admin token retrieval failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $token = $response->json('access_token');

        return is_string($token) && $token !== '' ? $token : null;
    }
}
