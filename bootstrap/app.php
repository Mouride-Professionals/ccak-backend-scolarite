<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use KeycloakGuard\Exceptions\TokenException;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->redirectTo(function (Request $request) {
            if ($request->is('api/*')) {
                abort(401, 'Unauthenticated.');
            }

            return '/login'; // or define a login route
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (UnauthorizedException $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => [],
            ], 403);
        });

        $exceptions->renderable(function (TokenException $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $message = $exception->getMessage();
            $prefix = '[Keycloak Guard] ';
            if (str_starts_with($message, $prefix)) {
                $message = substr($message, strlen($prefix));
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [],
            ], 401);
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Unauthorized.',
                'errors' => [],
            ], $exception->getStatusCode());
        });

        $exceptions->renderable(function (AuthenticationException $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Unauthenticated.',
                'errors' => [],
            ], 401);
        });
    })->create();
