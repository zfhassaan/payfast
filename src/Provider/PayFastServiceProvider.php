<?php

namespace zfhassaan\Payfast\Provider;

use zfhassaan\Payfast\PayFast;

class PayFastServiceProvider extends \Illuminate\Support\ServiceProvider {
    /**
     * Bootstrap the application Services.
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publishes config file to the main config folder
            $this->publishes([
                __DIR__ . '/../../config/config.php' => config_path('payfast.php'),
            ], 'config');

            // Publish Migrations to the database migration
            $this->publishes([
                __DIR__.'/../database/2023_08_14_071018_payfast_create_process_payments_table_in_payfast.php' => database_path('migrations'),
                __DIR__.'/..database/2024_02_02_194203_payfast_create_activity_logs_table.php' => database_path('migrations'),
                __DIR__.'/..database/2024_02_02_195511_payfast_create_ipn_table.php' => database_path('migrations'),
            ], 'payfast-migrations');
        }
    }

    /**
     * Register the application services.
     * @return void
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'payfast');

        // Register the main class to use with the facade
        $this->app->singleton('payfast', function () {
            return new PayFast;
        });
    }
}
