<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    public function logout(Request $request): JsonResponse
    {
        $keycloakUrl = rtrim((string) config('keycloak.server_url'), '/');
        $realm = (string) config('keycloak.realm');
        $clientId = (string) config('keycloak.client_id');
        $redirectUri = (string) config('keycloak.frontend_logout_redirect', config('app.url'));

        if ($keycloakUrl === '' || $realm === '') {
            return $this->error('Configuration Keycloak incomplète pour la déconnexion.', 500);
        }

        $params = [
            'post_logout_redirect_uri' => $redirectUri,
        ];

        if ($clientId !== '') {
            $params['client_id'] = $clientId;
        }

        $logoutUrl = sprintf(
            '%s/realms/%s/protocol/openid-connect/logout?%s',
            $keycloakUrl,
            $realm,
            http_build_query($params)
        );

        return $this->success(
            [
                'logout_url' => $logoutUrl,
                'message' => 'Veuillez rediriger l\'utilisateur vers logout_url pour finaliser la déconnexion.',
                'user_id' => $request->user()?->id,
            ],
            'Logout initiated'
        );
    }
}
