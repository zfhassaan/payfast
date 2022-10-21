<?php 

namespace zfhassaan\Payfast;

class PayFastServiceProvider extends \Illuminate\Support\ServiceProvider {
    /**
     * Bootstrap the application Services.
     */
    public function boot()
    {
        /**
         * Optional Methods to load the package assets. 
         * 
         */

        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'alfapay');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'alfapay');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . './config/config.php' => config_path('payfast.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/alfapay'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/alfapay'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/alfapay'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . './config/config.php', 'payfast');

        // Register the main class to use with the facade
        $this->app->singleton('payfast', function () {
            return new PayFast;
        });
    }
}