<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\ProcessPaymentRepository;

class ProcessPaymentRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private ProcessPaymentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ProcessPaymentRepository();
    }

    public function testCreatePayment(): void
    {
        $data = [
            'token' => 'test_token',
            'orderNo' => 'ORD-123',
            'transaction_id' => 'TXN-123',
            'data_3ds_secureid' => '3DS-123',
            'status' => ProcessPayment::STATUS_VALIDATED,
            'payment_method' => ProcessPayment::METHOD_CARD,
            'payload' => json_encode(['test' => 'data']),
            'requestData' => json_encode(['orderNumber' => 'ORD-123']),
        ];

        $payment = $this->repository->create($data);

        $this->assertInstanceOf(ProcessPayment::class, $payment);
        $this->assertEquals('ORD-123', $payment->orderNo);
        $this->assertEquals('TXN-123', $payment->transaction_id);
        $this->assertEquals(ProcessPayment::STATUS_VALIDATED, $payment->status);
    }

    public function testFindByTransactionId(): void
    {
        $payment = ProcessPayment::factory()->create([
            'transaction_id' => 'TXN-123',
        ]);

        $found = $this->repository->findByTransactionId('TXN-123');

        $this->assertNotNull($found);
        $this->assertEquals($payment->id, $found->id);
        $this->assertEquals('TXN-123', $found->transaction_id);
    }

    public function testFindByTransactionIdReturnsNullWhenNotFound(): void
    {
        $found = $this->repository->findByTransactionId('TXN-999');

        $this->assertNull($found);
    }

    public function testFindByBasketId(): void
    {
        $payment = ProcessPayment::factory()->create([
            'orderNo' => 'ORD-123',
        ]);

        $found = $this->repository->findByBasketId('ORD-123');

        $this->assertNotNull($found);
        $this->assertEquals($payment->id, $found->id);
        $this->assertEquals('ORD-123', $found->orderNo);
    }

    public function testFindByPares(): void
    {
        $payment = ProcessPayment::factory()->create([
            'data_3ds_pares' => 'pares_value_123',
        ]);

        $found = $this->repository->findByPares('pares_value_123');

        $this->assertNotNull($found);
        $this->assertEquals($payment->id, $found->id);
        $this->assertEquals('pares_value_123', $found->data_3ds_pares);
    }

    public function testUpdatePayment(): void
    {
        $payment = ProcessPayment::factory()->create([
            'status' => ProcessPayment::STATUS_VALIDATED,
        ]);

        $updated = $this->repository->update((string) $payment->id, [
            'status' => ProcessPayment::STATUS_OTP_VERIFIED,
            'data_3ds_pares' => 'pares_value_123',
        ]);

        $this->assertTrue($updated);

        $payment->refresh();
        $this->assertEquals(ProcessPayment::STATUS_OTP_VERIFIED, $payment->status);
        $this->assertEquals('pares_value_123', $payment->data_3ds_pares);
    }
}

