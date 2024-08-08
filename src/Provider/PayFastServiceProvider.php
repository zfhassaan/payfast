<?php

namespace zfhassaan\Payfast\Provider;

use zfhassaan\Payfast\PayFast;
use zfhassaan\Payfast\Services\PayFastService;

class PayFastServiceProvider extends \Illuminate\Support\ServiceProvider {
    /**
     * Bootstrap the application Services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
//            Publishes Config file to the main config folder
            $this->publishes([
                __DIR__.'/../../config/config.php'  => config_path('payfast.php'),
            ], 'config');

//            Publish Migrations to the database migration
            $this->publishes([
                __DIR__.'/../database/2023_08_14_071018_create_process_payments_table_in_payfast.php' => database_path('migrations'),
            ], 'payfast-migrations');


        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'payfast');

        // Register the main class to use with the facade
        $this->app->singleton('payfast', function () {
            return new PayFast(new PayFastService);
        });

        $this->app->singleton(PayFastService::class, PayfastService::class);

    }
}
