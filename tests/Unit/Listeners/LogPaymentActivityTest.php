<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Events\PaymentInitiated;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Listeners\LogPaymentActivity;
use zfhassaan\Payfast\Models\ActivityLog;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\ActivityLogRepositoryInterface;

class LogPaymentActivityTest extends TestCase
{
    private LogPaymentActivity $listener;
    private ActivityLogRepositoryInterface $activityLogRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityLogRepository = Mockery::mock(ActivityLogRepositoryInterface::class);
        $this->listener = new LogPaymentActivity($this->activityLogRepository);
    }

    public function testHandlePaymentInitiated(): void
    {
        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('info')->once()->with('Payment Initiated', Mockery::type('array'));

        Log::shouldReceive('channel')->once()->with('payfast')->andReturn($logChannel);

        $this->activityLogRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $data) => $data['status'] === ProcessPayment::STATUS_PENDING
                && $data['order_no'] === 'ORD-123'))
            ->andReturn(new ActivityLog());

        $event = new PaymentInitiated(
            ['orderNumber' => 'ORD-123'],
            ['code' => '00']
        );

        $this->listener->handlePaymentInitiated($event);

        $this->assertEquals('ORD-123', $event->paymentData['orderNumber']);
    }

    public function testHandlePaymentValidated(): void
    {
        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('info')->once()->with('Payment Validated', Mockery::type('array'));

        Log::shouldReceive('channel')->once()->with('payfast')->andReturn($logChannel);

        $this->activityLogRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $data) => $data['status'] === ProcessPayment::STATUS_VALIDATED
                && $data['transaction_id'] === 'TXN-123'))
            ->andReturn(new ActivityLog());

        $event = new PaymentValidated(
            ['orderNumber' => 'ORD-123'],
            ['transaction_id' => 'TXN-123']
        );

        $this->listener->handlePaymentValidated($event);

        $this->assertEquals('TXN-123', $event->validationResponse['transaction_id']);
    }

    public function testHandlePaymentCompleted(): void
    {
        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('info')->once()->with('Payment Completed', Mockery::type('array'));

        Log::shouldReceive('channel')->once()->with('payfast')->andReturn($logChannel);

        $this->activityLogRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $data) => $data['status'] === ProcessPayment::STATUS_COMPLETED))
            ->andReturn(new ActivityLog());

        $event = new PaymentCompleted(
            ['transaction_id' => 'TXN-123'],
            ['code' => '00']
        );

        $this->listener->handlePaymentCompleted($event);

        $this->assertEquals('00', $event->response['code']);
    }

    public function testHandlePaymentFailed(): void
    {
        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('error')->once()->with('Payment Failed', Mockery::type('array'));

        Log::shouldReceive('channel')->once()->with('payfast')->andReturn($logChannel);

        $this->activityLogRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $data) => $data['status'] === ProcessPayment::STATUS_FAILED))
            ->andReturn(new ActivityLog());

        $event = new PaymentFailed(
            ['orderNumber' => 'ORD-123'],
            '14',
            'Invalid card details'
        );

        $this->listener->handlePaymentFailed($event);

        $this->assertEquals('14', $event->errorCode);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
