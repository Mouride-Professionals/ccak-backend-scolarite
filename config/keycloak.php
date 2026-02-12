<?php

return [
    'server_url' => rtrim((string) env('KEYCLOAK_SERVER_URL', 'http://localhost:8080'), '/'),
    'realm' => env('KEYCLOAK_REALM', 'master'),
    'admin_api_enabled' => filter_var(env('KEYCLOAK_ADMIN_API_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'client_id' => env('KEYCLOAK_CLIENT_ID', ''),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET', ''),
    'admin_client_id' => env('KEYCLOAK_ADMIN_CLIENT_ID', 'admin-cli'),
    'admin_client_secret' => env('KEYCLOAK_ADMIN_CLIENT_SECRET', ''),
    'admin_username' => env('KEYCLOAK_ADMIN_USERNAME', ''),
    'admin_password' => env('KEYCLOAK_ADMIN_PASSWORD', ''),
    'default_temporary_password' => env('KEYCLOAK_DEFAULT_TEMP_PASSWORD', 'ChangeMe123!'),
    'frontend_logout_redirect' => env('KEYCLOAK_FRONTEND_LOGOUT_REDIRECT', env('APP_URL', 'http://localhost')),

    // Sync Keycloak roles into Spatie Permission.
    'sync_roles' => filter_var(env('KEYCLOAK_SYNC_ROLES', false), FILTER_VALIDATE_BOOLEAN),

    // Allow-list of roles to sync/use from Keycloak.
    'role_allowlist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('KEYCLOAK_ROLE_ALLOWLIST', 'ADMIN,FACULTY,STUDENT,STAFF'))
    ))),

    // Package (robsontenorio/laravel-keycloak-guard) configuration.
    'realm_public_key' => env('KEYCLOAK_REALM_PUBLIC_KEY', null),
    'token_encryption_algorithm' => env('KEYCLOAK_TOKEN_ENCRYPTION_ALGORITHM', 'RS256'),
    'load_user_from_database' => filter_var(env('KEYCLOAK_LOAD_USER_FROM_DATABASE', true), FILTER_VALIDATE_BOOLEAN),
    'user_provider_custom_retrieve_method' => env('KEYCLOAK_USER_PROVIDER_CUSTOM_RETRIEVE_METHOD', null),
    'user_provider_credential' => env('KEYCLOAK_USER_PROVIDER_CREDENTIAL', 'username'),
    'token_principal_attribute' => env('KEYCLOAK_TOKEN_PRINCIPAL_ATTRIBUTE', 'preferred_username'),
    'append_decoded_token' => filter_var(env('KEYCLOAK_APPEND_DECODED_TOKEN', false), FILTER_VALIDATE_BOOLEAN),
    'allowed_resources' => env('KEYCLOAK_ALLOWED_RESOURCES', null),
    'ignore_resources_validation' => filter_var(env('KEYCLOAK_IGNORE_RESOURCES_VALIDATION', false), FILTER_VALIDATE_BOOLEAN),
    'leeway' => (int) env('KEYCLOAK_LEEWAY', 0),
    'input_key' => env('KEYCLOAK_TOKEN_INPUT_KEY', null),
];
