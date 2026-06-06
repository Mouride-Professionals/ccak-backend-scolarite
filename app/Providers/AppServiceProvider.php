<?php

namespace App\Providers;

use App\Auth\KeycloakUserProvider;
use App\Models\User;
use App\Observers\UserObserver;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('keycloak-eloquent', function ($app, array $config) {
            return new KeycloakUserProvider($app['hash'], $config['model']);
        });

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(SecurityScheme::http('bearer', 'JWT'));
            });

        Gate::define('viewApiDocs', function (?User $user): bool {
            if (! config('scramble.require_auth')) {
                return true;
            }

            if (! $user) {
                return false;
            }

            $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('ADMIN');

            return $isAdmin
                || $user->can('permissions.view')
                || $user->can('roles.view');
        });
        User::observe(UserObserver::class);
    }
}
