<?php
namespace Tests;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        // Check before booting providers; cached production config is never allowed.
        if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
            throw new \RuntimeException('Refusing tests with cached application configuration.');
        }
        $environment = $_SERVER['APP_ENV'] ?? getenv('APP_ENV');
        $database = $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE');
        if ($environment !== 'testing' || $database !== 'booktkit_test') {
            throw new \RuntimeException('Tests require APP_ENV=testing and the dedicated booktkit_test database.');
        }
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        if (!$app->environment('testing') || config('database.default') !== 'mysql' ||
            config('database.connections.mysql.database') !== 'booktkit_test') {
            throw new \RuntimeException('Unsafe resolved test database configuration.');
        }
        return $app;
    }
}
