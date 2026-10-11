<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\Console\CABPayments;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\ActivityLogRepositoryInterface;
use zfhassaan\Payfast\Repositories\ProcessPaymentRepository;
use zfhassaan\Payfast\Services\ConfigService;
use zfhassaan\Payfast\Services\Contracts\EmailNotificationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;

class CABPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private HttpClientInterface $httpClient;
    private ConfigService $configService;
    private ActivityLogRepositoryInterface $activityLogRepository;
    private EmailNotificationServiceInterface $emailNotificationService;
    private ProcessPaymentRepository $paymentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = Mockery::mock(HttpClientInterface::class);
        $this->configService = new ConfigService();
        $this->activityLogRepository = Mockery::mock(ActivityLogRepositoryInterface::class);
        $this->emailNotificationService = Mockery::mock(EmailNotificationServiceInterface::class);
        $this->paymentRepository = new ProcessPaymentRepository();

        $command = $this->makeCommand();

        $this->app->instance(CABPayments::class, $command);

        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);
        if (method_exists($kernel, 'registerCommand')) {
            $kernel->registerCommand($command);
        } else {
            $this->app['artisan']->add($command);
        }

        config([
            'payfast.transaction_check' => 'https://api.payfast.test/',
        ]);
    }

    private function makeCommand(): CABPayments
    {
        return new CABPayments(
            $this->httpClient,
            $this->configService,
            $this->activityLogRepository,
            $this->emailNotificationService,
            $this->paymentRepository
        );
    }

    public function testCommandProcessesPendingPayments(): void
    {
        ProcessPayment::factory()->count(3)->create([
            'status' => ProcessPayment::STATUS_PENDING,
        ]);

        $this->httpClient
            ->shouldReceive('get')
            ->times(3)
            ->andReturn([
                'code' => '00',
                'status' => 'completed',
            ]);

        $this->activityLogRepository
            ->shouldReceive('create')
            ->andReturn(new \zfhassaan\Payfast\Models\ActivityLog());

        $this->emailNotificationService
            ->shouldReceive('sendPaymentCompletionEmail')
            ->times(3);

        $this->emailNotificationService
            ->shouldReceive('sendAdminNotificationEmail')
            ->times(3);

        $command = $this->makeCommand();
        $input = new \Symfony\Component\Console\Input\ArrayInput([], $command->getDefinition());
        $output = new \Illuminate\Console\OutputStyle($input, new \Symfony\Component\Console\Output\BufferedOutput());
        $command->setInput($input);
        $command->setOutput($output);

        $result = $command->handle();

        $this->assertEquals(0, $result);
    }

    public function testCommandFiltersByStatus(): void
    {
        ProcessPayment::factory()->create(['status' => ProcessPayment::STATUS_PENDING]);
        ProcessPayment::factory()->create(['status' => ProcessPayment::STATUS_VALIDATED]);
        ProcessPayment::factory()->create(['status' => ProcessPayment::STATUS_OTP_VERIFIED]);

        $this->httpClient
            ->shouldReceive('get')
            ->once()
            ->andReturn(['code' => '00']);

        $this->activityLogRepository
            ->shouldReceive('create')
            ->andReturn(new \zfhassaan\Payfast\Models\ActivityLog());

        $this->emailNotificationService
            ->shouldReceive('sendPaymentCompletionEmail')
            ->once();

        $this->emailNotificationService
            ->shouldReceive('sendAdminNotificationEmail')
            ->once();

        $command = $this->makeCommand();
        $input = new \Symfony\Component\Console\Input\ArrayInput(['--status' => 'otp_verified'], $command->getDefinition());
        $output = new \Illuminate\Console\OutputStyle($input, new \Symfony\Component\Console\Output\BufferedOutput());
        $command->setInput($input);
        $command->setOutput($output);

        $result = $command->handle();

        $this->assertEquals(0, $result);
    }

    public function testCommandRespectsLimit(): void
    {
        ProcessPayment::factory()->count(10)->create([
            'status' => ProcessPayment::STATUS_PENDING,
        ]);

        $this->httpClient
            ->shouldReceive('get')
            ->times(5)
            ->andReturn(['code' => '00']);

        $this->activityLogRepository
            ->shouldReceive('create')
            ->andReturn(new \zfhassaan\Payfast\Models\ActivityLog());

        $this->emailNotificationService
            ->shouldReceive('sendPaymentCompletionEmail')
            ->times(5);

        $this->emailNotificationService
            ->shouldReceive('sendAdminNotificationEmail')
            ->times(5);

        $command = $this->makeCommand();
        $input = new \Symfony\Component\Console\Input\ArrayInput(['--limit' => 5], $command->getDefinition());
        $output = new \Illuminate\Console\OutputStyle($input, new \Symfony\Component\Console\Output\BufferedOutput());
        $command->setInput($input);
        $command->setOutput($output);

        $result = $command->handle();

        $this->assertEquals(0, $result);
    }

    public function testCommandHandlesNoPayments(): void
    {
        $command = $this->makeCommand();
        $input = new \Symfony\Component\Console\Input\ArrayInput([], $command->getDefinition());
        $output = new \Illuminate\Console\OutputStyle($input, new \Symfony\Component\Console\Output\BufferedOutput());
        $command->setInput($input);
        $command->setOutput($output);

        $result = $command->handle();

        $this->assertEquals(0, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
