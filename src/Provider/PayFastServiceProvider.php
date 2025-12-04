<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Provider;

use Illuminate\Support\ServiceProvider;
use zfhassaan\Payfast\PayFast;
use zfhassaan\Payfast\Repositories\ActivityLogRepository;
use zfhassaan\Payfast\Repositories\Contracts\ActivityLogRepositoryInterface;
use zfhassaan\Payfast\Repositories\Contracts\IPNLogRepositoryInterface;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;
use zfhassaan\Payfast\Repositories\IPNLogRepository;
use zfhassaan\Payfast\Repositories\ProcessPaymentRepository;
use zfhassaan\Payfast\Services\AuthenticationService;
use zfhassaan\Payfast\Services\ConfigService;
use zfhassaan\Payfast\Services\Contracts\AuthenticationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\EmailNotificationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;
use zfhassaan\Payfast\Services\Contracts\OTPVerificationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\PaymentServiceInterface;
use zfhassaan\Payfast\Services\Contracts\TransactionServiceInterface;
use zfhassaan\Payfast\Services\EmailNotificationService;
use zfhassaan\Payfast\Services\HttpClientService;
use zfhassaan\Payfast\Services\OTPVerificationService;
use zfhassaan\Payfast\Services\PaymentService;
use zfhassaan\Payfast\Services\TransactionService;

class PayFastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application Services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config file to the main config folder
            $this->publishes([
                __DIR__ . '/../../config/config.php' => config_path('payfast.php'),
            ], 'payfast-config');

            // Publish Migrations to the database migration
            $this->publishes([
                __DIR__ . '/../database/migrations/2023_08_14_071018_payfast_create_process_payments_table_in_payfast.php' => database_path('migrations'),
                __DIR__ . '/../database/migrations/2024_02_02_194203_payfast_create_activity_logs_table.php' => database_path('migrations'),
                __DIR__ . '/../database/migrations/2024_02_02_195511_payfast_create_ipn_table.php' => database_path('migrations'),
                __DIR__ . '/../database/migrations/2025_01_15_000001_add_status_and_pares_to_process_payments.php' => database_path('migrations'),
            ], 'payfast-migrations');

            // Publish email templates
            $this->publishes([
                __DIR__ . '/../resources/views/emails' => resource_path('views/vendor/payfast/emails'),
            ], 'payfast-email-templates');

            // Publish console command (stub for customization)
            $this->publishes([
                __DIR__ . '/../Console/CABPayments.php.stub' => app_path('Console/Commands/PayfastCheckPendingPayments.php'),
            ], 'payfast-command');

            // Register event listeners
            $this->registerEventListeners();
        }

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'payfast');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'payfast');

        // Register services
        $this->app->singleton(ConfigService::class);
        $this->app->singleton(HttpClientInterface::class, HttpClientService::class);
        $this->app->singleton(AuthenticationServiceInterface::class, AuthenticationService::class);
        $this->app->singleton(PaymentServiceInterface::class, PaymentService::class);
        $this->app->singleton(TransactionServiceInterface::class, TransactionService::class);
        $this->app->singleton(OTPVerificationServiceInterface::class, OTPVerificationService::class);

        // Register repositories
        $this->app->singleton(ProcessPaymentRepositoryInterface::class, ProcessPaymentRepository::class);
        $this->app->singleton(ActivityLogRepositoryInterface::class, ActivityLogRepository::class);
        $this->app->singleton(IPNLogRepositoryInterface::class, IPNLogRepository::class);

        // Register email notification service
        $this->app->singleton(EmailNotificationServiceInterface::class, EmailNotificationService::class);

        // Register the main PayFast class
        $this->app->singleton('payfast', function ($app) {
            return new PayFast(
                $app->make(AuthenticationServiceInterface::class),
                $app->make(PaymentServiceInterface::class),
                $app->make(TransactionServiceInterface::class),
                $app->make(OTPVerificationServiceInterface::class),
                $app->make(ProcessPaymentRepositoryInterface::class)
            );
        });
    }

    /**
     * Register event listeners.
     *
     * @return void
     */
    private function registerEventListeners(): void
    {
        $events = [
            \zfhassaan\Payfast\Events\PaymentInitiated::class => [
                \zfhassaan\Payfast\Listeners\LogPaymentActivity::class . '@handlePaymentInitiated',
            ],
            \zfhassaan\Payfast\Events\PaymentValidated::class => [
                \zfhassaan\Payfast\Listeners\LogPaymentActivity::class . '@handlePaymentValidated',
                \zfhassaan\Payfast\Listeners\StorePaymentRecord::class . '@handle',
                \zfhassaan\Payfast\Listeners\SendPaymentEmailNotifications::class . '@handlePaymentValidated',
            ],
            \zfhassaan\Payfast\Events\PaymentCompleted::class => [
                \zfhassaan\Payfast\Listeners\LogPaymentActivity::class . '@handlePaymentCompleted',
                \zfhassaan\Payfast\Listeners\SendPaymentEmailNotifications::class . '@handlePaymentCompleted',
            ],
            \zfhassaan\Payfast\Events\PaymentFailed::class => [
                \zfhassaan\Payfast\Listeners\LogPaymentActivity::class . '@handlePaymentFailed',
                \zfhassaan\Payfast\Listeners\SendPaymentEmailNotifications::class . '@handlePaymentFailed',
            ],
        ];

        foreach ($events as $event => $listeners) {
            foreach ($listeners as $listener) {
                \Illuminate\Support\Facades\Event::listen($event, $listener);
            }
        }
    }
}
