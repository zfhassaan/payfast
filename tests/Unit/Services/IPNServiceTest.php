<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Models\IPNLog;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\IPNLogRepository;
use zfhassaan\Payfast\Repositories\ProcessPaymentRepository;
use zfhassaan\Payfast\Services\IPNService;

class IPNServiceTest extends TestCase
{
    use RefreshDatabase;

    private IPNService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payfast.idempotency.enabled' => true]);

        $this->service = new IPNService(
            new IPNLogRepository(),
            new ProcessPaymentRepository()
        );
    }

    public function testProcessIpnPersistsAndCompletesPayment(): void
    {
        Event::fake([PaymentCompleted::class]);

        ProcessPayment::factory()->create([
            'transaction_id' => 'TXN-100',
            'orderNo' => 'ORD-100',
            'status' => ProcessPayment::STATUS_VALIDATED,
        ]);

        $result = $this->service->processIPN([
            'transaction_id' => 'TXN-100',
            'basket_id' => 'ORD-100',
            'status' => '00',
            'amount' => 500,
            'currency' => 'PKR',
        ]);

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);
        $this->assertTrue($result['data']['payment_updated']);

        $this->assertDatabaseHas('payfast_ipn_table', [
            'transaction_id' => 'TXN-100',
            'processing_status' => IPNLog::STATUS_PROCESSED,
        ]);

        $this->assertEquals(
            ProcessPayment::STATUS_COMPLETED,
            ProcessPayment::where('transaction_id', 'TXN-100')->value('status')
        );

        Event::assertDispatched(PaymentCompleted::class);
    }

    public function testDuplicateIpnIsIdempotentAndStillLogged(): void
    {
        Event::fake([PaymentCompleted::class]);

        ProcessPayment::factory()->create([
            'transaction_id' => 'TXN-DUP',
            'orderNo' => 'ORD-DUP',
            'status' => ProcessPayment::STATUS_VALIDATED,
        ]);

        $payload = [
            'transaction_id' => 'TXN-DUP',
            'basket_id' => 'ORD-DUP',
            'status' => '00',
            'amount' => 100,
        ];

        $first = $this->service->processIPN($payload);
        $second = $this->service->processIPN($payload);

        $this->assertEquals('00', $first['code']);
        $this->assertEquals('ALREADY_PROCESSED', $second['code']);
        $this->assertFalse($second['data']['payment_updated']);

        $this->assertSame(2, IPNLog::where('transaction_id', 'TXN-DUP')->count());
        $this->assertSame(1, IPNLog::where('transaction_id', 'TXN-DUP')
            ->where('processing_status', IPNLog::STATUS_PROCESSED)->count());
        $this->assertSame(1, IPNLog::where('transaction_id', 'TXN-DUP')
            ->where('processing_status', IPNLog::STATUS_DUPLICATE)->count());

        Event::assertDispatchedTimes(PaymentCompleted::class, 1);
    }

    public function testDoesNotRegressCompletedPaymentOnFailedIpn(): void
    {
        ProcessPayment::factory()->create([
            'transaction_id' => 'TXN-DONE',
            'orderNo' => 'ORD-DONE',
            'status' => ProcessPayment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $result = $this->service->updatePaymentStatus([
            'transaction_id' => 'TXN-DONE',
            'status' => 'failed',
        ]);

        $this->assertFalse($result['updated']);
        $this->assertEquals(
            ProcessPayment::STATUS_COMPLETED,
            ProcessPayment::where('transaction_id', 'TXN-DONE')->value('status')
        );
    }

    public function testInvalidPayloadIsLoggedAsRejected(): void
    {
        $result = $this->service->processIPN([
            'amount' => 10,
        ]);

        $this->assertFalse($result['status']);
        $this->assertEquals('INVALID_IPN', $result['code']);
        $this->assertDatabaseHas('payfast_ipn_table', [
            'processing_status' => IPNLog::STATUS_REJECTED,
        ]);
    }
}
