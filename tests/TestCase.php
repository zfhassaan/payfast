<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use zfhassaan\Payfast\Provider\PayFastServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load migrations - RefreshDatabase will handle running them
        // Try multiple paths to support both package development and published tests
        $possiblePaths = [
            __DIR__ . '/../src/database/migrations', // Package development
            base_path('vendor/zfhassaan/payfast/src/database/migrations'), // Published from vendor
            base_path('packages/zfhassaan/payfast/src/database/migrations'), // Local package
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $this->loadMigrationsFrom($path);
                break;
            }
        }
        
        // Run migrations if not already run
        if (!$this->app['migrator']->repositoryExists()) {
            $this->artisan('migrate', ['--database' => 'testbench'])->run();
        }

        // Set test configuration
        $this->app['config']->set('payfast.api_url', 'https://api.payfast.test');
        $this->app['config']->set('payfast.sandbox_api_url', 'https://sandbox.payfast.test');
        $this->app['config']->set('payfast.merchant_id', 'test_merchant_id');
        $this->app['config']->set('payfast.secured_key', 'test_secured_key');
        $this->app['config']->set('payfast.grant_type', 'client_credentials');
        $this->app['config']->set('payfast.return_url', 'https://example.com/callback');
        $this->app['config']->set('payfast.mode', 'sandbox');
        $this->app['config']->set('payfast.store_id', 'test_store_id');
        $this->app['config']->set('payfast.admin_emails', 'admin@example.com');
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PayFastServiceProvider::class,
        ];
    }
}

