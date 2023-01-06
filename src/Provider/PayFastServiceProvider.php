<?php

namespace zfhassaan\Payfast\Provider;

class PayFastServiceProvider extends \Illuminate\Support\ServiceProvider {
    /**
     * Bootstrap the application Services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/config.php'  => config_path('payfast.php'),
            ], 'config');
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
            return new PayFast;
        });
    }
}
