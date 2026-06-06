<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\KeycloakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserController extends BaseApiController
{
    public function __construct(
        private readonly KeycloakService $keycloakService
    ) {
        $this->middleware('permission:users.create')->only('store');
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.update')->only('update');
        $this->middleware('permission:users.delete')->only('destroy');
    }

    /**
     * Create a new user with Keycloak integration.
     *
     * For Faculty, Admin, Staff - creates both local User and Keycloak account.
     * For Students - use StudentController instead (creates Student profile too).
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        try {
            // ALL-OR-NOTHING TRANSACTION: If any step fails, rollback everything
            $user = DB::transaction(function () use ($request) {
                // Step 1: Create Keycloak user FIRST (fail fast if Keycloak is down)
                $keycloakUserId = $this->createKeycloakUser($request);

                if (! $keycloakUserId) {
                    throw new \RuntimeException('Échec de la création de l\'utilisateur Keycloak.');
                }

                Log::info('Keycloak user created', ['keycloak_id' => $keycloakUserId]);

                // Step 2: Create local user with Keycloak ID
                $user = User::create([
                    'email' => $request->email,
                    'keycloak_id' => $keycloakUserId,
                    'is_active' => $request->is_active ?? true,
                ]);

                Log::info('Local user created', ['user_id' => $user->id, 'email' => $user->email]);

                // Step 3: Assign role locally (Spatie)
                if ($request->role) {
                    $user->assignRole($request->role);
                    Log::info('Local role assigned', ['user_id' => $user->id, 'role' => $request->role]);
                }

                return $user;
            });

            // Transaction committed - all steps succeeded
            return $this->success(
                new UserResource($user->load('roles')),
                'Utilisateur créé avec succès.',
                201
            );

        } catch (\Exception $e) {
            // Transaction rolled back - nothing was created
            Log::error('User creation failed - transaction rolled back', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Erreur lors de la création de l\'utilisateur: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Create user in Keycloak and assign role.
     * Throws exception on failure (for transaction rollback).
     *
     * @throws \RuntimeException If Keycloak user creation fails
     */
    private function createKeycloakUser(StoreUserRequest $request): ?string
    {
        // Check if Keycloak is configured
        if (! $this->keycloakService->isConfigured()) {
            throw new \RuntimeException('Keycloak n\'est pas configuré.');
        }

        // Parse name for Keycloak user creation
        $names = $this->parseName($request->full_name ?? $request->email);

        // Generate temporary username (will be replaced with final once we have user ID)
        $tempUsername = $this->generateTempUsername($request);

        // Create Keycloak user
        $keycloakUserId = $this->keycloakService->createUser([
            'username' => $tempUsername,
            'email' => $request->email,
            'first_name' => $names['first_name'],
            'last_name' => $names['last_name'],
            'temporary_password' => $request->temporary_password
                ?? config('keycloak.default_temporary_password', Str::random(12)),
        ]);

        if (! $keycloakUserId) {
            throw new \RuntimeException('La création de l\'utilisateur Keycloak a échoué.');
        }

        // Assign role in Keycloak
        if ($request->role) {
            $roleAssigned = $this->keycloakService->assignRealmRole(
                $keycloakUserId,
                strtoupper($request->role)
            );

            if (! $roleAssigned) {
                throw new \RuntimeException("L'attribution du rôle Keycloak '{$request->role}' a échoué.");
            }

            Log::info('Keycloak role assigned', [
                'keycloak_id' => $keycloakUserId,
                'role' => $request->role,
            ]);
        }

        return $keycloakUserId;
    }

    /**
     * Generate temporary username before User creation.
     * Will be used for Keycloak user creation when we don't have local User ID yet.
     */
    private function generateTempUsername(StoreUserRequest $request): string
    {
        // Use employee number if provided (for faculty/staff)
        if ($request->employee_number) {
            return $request->employee_number;
        }

        // Use email prefix with random suffix to ensure uniqueness
        $emailPrefix = explode('@', $request->email)[0];

        return $emailPrefix.'_'.substr(Str::uuid()->toString(), 0, 8);
    }

    /**
     * Parse full name into first and last name.
     */
    private function parseName(?string $fullName): array
    {
        if (! $fullName) {
            return ['first_name' => 'User', 'last_name' => 'Unknown'];
        }

        $names = preg_split('/\s+/', trim($fullName)) ?: [];
        $firstName = $names[0] ?? 'User';
        $lastName = count($names) > 1 ? implode(' ', array_slice($names, 1)) : 'Unknown';

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];
    }
}
