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
use zfhassaan\Payfast\Services\Contracts\IPNServiceInterface;
use zfhassaan\Payfast\Services\Contracts\OTPVerificationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\PaymentServiceInterface;
use zfhassaan\Payfast\Services\Contracts\SubscriptionServiceInterface;
use zfhassaan\Payfast\Services\Contracts\TransactionServiceInterface;
use zfhassaan\Payfast\Services\EmailNotificationService;
use zfhassaan\Payfast\Services\HttpClientService;
use zfhassaan\Payfast\Services\IPNService;
use zfhassaan\Payfast\Services\OTPVerificationService;
use zfhassaan\Payfast\Services\PaymentService;
use zfhassaan\Payfast\Services\SubscriptionService;
use zfhassaan\Payfast\Services\TransactionService;

class PayFastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application Services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishes();

            $this->commands([
                \zfhassaan\Payfast\Console\CABPayments::class,
            ]);
        }

        // Listeners must register for HTTP + console (not console-only).
        $this->registerEventListeners();

        if (config('payfast.load_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'payfast');

        if (config('payfast.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        }

        $router = $this->app['router'];
        $router->aliasMiddleware(
            'payfast.ipn.log',
            \zfhassaan\Payfast\Http\Middleware\LogIncomingIpn::class
        );
        $router->aliasMiddleware(
            'payfast.ipn',
            \zfhassaan\Payfast\Http\Middleware\VerifyPayfastIpn::class
        );
    }

    /**
     * Register publishable assets for vendor:publish.
     */
    private function registerPublishes(): void
    {
        $config = [
            __DIR__ . '/../../config/config.php' => config_path('payfast.php'),
        ];

        $migrations = [
            __DIR__ . '/../database/migrations/2023_08_14_071018_payfast_create_process_payments_table_in_payfast.php'
                => database_path('migrations/2023_08_14_071018_payfast_create_process_payments_table_in_payfast.php'),
            __DIR__ . '/../database/migrations/2024_02_02_194203_payfast_create_activity_logs_table.php'
                => database_path('migrations/2024_02_02_194203_payfast_create_activity_logs_table.php'),
            __DIR__ . '/../database/migrations/2024_02_02_195511_payfast_create_ipn_table.php'
                => database_path('migrations/2024_02_02_195511_payfast_create_ipn_table.php'),
            __DIR__ . '/../database/migrations/2025_01_15_000001_add_status_and_pares_to_process_payments.php'
                => database_path('migrations/2025_01_15_000001_add_status_and_pares_to_process_payments.php'),
            __DIR__ . '/../database/migrations/2025_05_06_000001_add_raw_payload_and_ip_to_ipn_table.php'
                => database_path('migrations/2025_05_06_000001_add_raw_payload_and_ip_to_ipn_table.php'),
            __DIR__ . '/../database/migrations/2026_03_22_000001_add_idempotency_and_processing_to_ipn_table.php'
                => database_path('migrations/2026_03_22_000001_add_idempotency_and_processing_to_ipn_table.php'),
        ];

        $views = [
            __DIR__ . '/../resources/views/emails' => resource_path('views/vendor/payfast/emails'),
        ];

        $command = [
            __DIR__ . '/../Console/CABPayments.php.stub' => app_path('Console/Commands/PayfastCheckPendingPayments.php'),
        ];

        $logging = [
            __DIR__ . '/../../config/logging-channel.php' => config_path('payfast-logging-channel.php'),
        ];

        $envExample = [
            __DIR__ . '/../../.env.example' => base_path('.env.payfast.example'),
        ];

        $tests = [
            __DIR__ . '/../../tests/TestCase.php' => base_path('tests/PayFast/TestCase.php'),
            __DIR__ . '/../../tests/Unit/TestCase.php' => base_path('tests/Unit/PayFast/TestCase.php'),
            __DIR__ . '/../../tests/Unit/PayFastTest.php' => base_path('tests/Unit/PayFast/PayFastTest.php'),
            __DIR__ . '/../../tests/Unit/Console/CABPaymentsTest.php' => base_path('tests/Unit/PayFast/Console/CABPaymentsTest.php'),
            __DIR__ . '/../../tests/Unit/DTOs/PaymentRequestDTOTest.php' => base_path('tests/Unit/PayFast/DTOs/PaymentRequestDTOTest.php'),
            __DIR__ . '/../../tests/Unit/DTOs/WalletPaymentRequestDTOTest.php' => base_path('tests/Unit/PayFast/DTOs/WalletPaymentRequestDTOTest.php'),
            __DIR__ . '/../../tests/Unit/Events/PaymentEventsTest.php' => base_path('tests/Unit/PayFast/Events/PaymentEventsTest.php'),
            __DIR__ . '/../../tests/Unit/Helpers/UtilityTest.php' => base_path('tests/Unit/PayFast/Helpers/UtilityTest.php'),
            __DIR__ . '/../../tests/Unit/Listeners/LogPaymentActivityTest.php' => base_path('tests/Unit/PayFast/Listeners/LogPaymentActivityTest.php'),
            __DIR__ . '/../../tests/Unit/Models/ProcessPaymentTest.php' => base_path('tests/Unit/PayFast/Models/ProcessPaymentTest.php'),
            __DIR__ . '/../../tests/Unit/Repositories/ProcessPaymentRepositoryTest.php' => base_path('tests/Unit/PayFast/Repositories/ProcessPaymentRepositoryTest.php'),
            __DIR__ . '/../../tests/Unit/Services/AuthenticationServiceTest.php' => base_path('tests/Unit/PayFast/Services/AuthenticationServiceTest.php'),
            __DIR__ . '/../../tests/Unit/Services/OTPVerificationServiceTest.php' => base_path('tests/Unit/PayFast/Services/OTPVerificationServiceTest.php'),
            __DIR__ . '/../../tests/Unit/Services/PaymentServiceTest.php' => base_path('tests/Unit/PayFast/Services/PaymentServiceTest.php'),
            __DIR__ . '/../../tests/Unit/Services/TransactionServiceTest.php' => base_path('tests/Unit/PayFast/Services/TransactionServiceTest.php'),
            __DIR__ . '/../../tests/Unit/Http/Requests/FormRequestTest.php' => base_path('tests/Unit/PayFast/Http/Requests/FormRequestTest.php'),
            __DIR__ . '/../../tests/Unit/Http/Middleware/VerifyPayfastIpnTest.php' => base_path('tests/Unit/PayFast/Http/Middleware/VerifyPayfastIpnTest.php'),
            __DIR__ . '/../../tests/Feature/PaymentFlowTest.php' => base_path('tests/Feature/PayFast/PaymentFlowTest.php'),
        ];

        // Individual tags
        $this->publishes($config, 'payfast-config');
        $this->publishes($migrations, 'payfast-migrations');
        $this->publishes($views, 'payfast-email-templates');
        $this->publishes($command, 'payfast-command');
        $this->publishes($logging, 'payfast-logging');
        $this->publishes($envExample, 'payfast-env');
        $this->publishes($tests, 'payfast-tests');

        // Umbrella tag — publish everything most apps need to customize
        $this->publishes(
            array_merge($config, $migrations, $views, $logging, $envExample),
            'payfast'
        );
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'payfast');

        $this->app->singleton(ConfigService::class);
        $this->app->singleton(HttpClientInterface::class, HttpClientService::class);
        $this->app->singleton(AuthenticationServiceInterface::class, AuthenticationService::class);
        $this->app->singleton(PaymentServiceInterface::class, PaymentService::class);
        $this->app->singleton(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->singleton(TransactionServiceInterface::class, TransactionService::class);
        $this->app->singleton(OTPVerificationServiceInterface::class, OTPVerificationService::class);

        $this->app->singleton(ProcessPaymentRepositoryInterface::class, ProcessPaymentRepository::class);
        $this->app->singleton(ActivityLogRepositoryInterface::class, ActivityLogRepository::class);
        $this->app->singleton(IPNLogRepositoryInterface::class, IPNLogRepository::class);

        $this->app->singleton(EmailNotificationServiceInterface::class, EmailNotificationService::class);
        $this->app->singleton(IPNServiceInterface::class, IPNService::class);

        $this->app->singleton('payfast', function ($app) {
            return new PayFast(
                $app->make(AuthenticationServiceInterface::class),
                $app->make(PaymentServiceInterface::class),
                $app->make(SubscriptionServiceInterface::class),
                $app->make(TransactionServiceInterface::class),
                $app->make(OTPVerificationServiceInterface::class),
                $app->make(ProcessPaymentRepositoryInterface::class),
                $app->make(IPNServiceInterface::class)
            );
        });
    }

    /**
     * Register event listeners.
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
