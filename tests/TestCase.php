<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $configCache = __DIR__.'/../bootstrap/cache/config.php';
        if (file_exists($configCache)) {
            @unlink($configCache);
        }
        $routesCache = __DIR__.'/../bootstrap/cache/routes-v7.php';
        if (file_exists($routesCache)) {
            @unlink($routesCache);
        }

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'auth.defaults.guard' => 'api',
            'auth.guards.api' => config('auth.guards.api', [
                'driver' => 'keycloak',
                'provider' => 'keycloak_users',
            ]),
            'filesystems.default' => 'documents',
            'media-library.disk_name' => 'documents',
        ]);
    }
}
