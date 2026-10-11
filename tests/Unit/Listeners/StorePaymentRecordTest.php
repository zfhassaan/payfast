<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Listeners\StorePaymentRecord;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;

class StorePaymentRecordTest extends TestCase
{
    public function testSkipsCreateWhenTransactionAlreadyExists(): void
    {
        $repo = Mockery::mock(ProcessPaymentRepositoryInterface::class);
        $repo->shouldReceive('findByTransactionId')
            ->once()
            ->with('TXN-1')
            ->andReturn(ProcessPayment::factory()->make(['transaction_id' => 'TXN-1']));
        $repo->shouldNotReceive('create');

        $listener = new StorePaymentRecord($repo);
        $listener->handle(new PaymentValidated(
            ['basket_id' => 'ORD-1', 'token' => 't'],
            ['transaction_id' => 'TXN-1', 'data_3ds_secureid' => '3ds']
        ));

        $this->assertTrue(true);
    }

    public function testCreatesWhenNoExistingRecord(): void
    {
        $repo = Mockery::mock(ProcessPaymentRepositoryInterface::class);
        $repo->shouldReceive('findByTransactionId')->once()->with('TXN-2')->andReturn(null);
        $repo->shouldReceive('findByBasketId')->once()->with('ORD-2')->andReturn(null);
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $data) => $data['transaction_id'] === 'TXN-2' && $data['orderNo'] === 'ORD-2'))
            ->andReturn(ProcessPayment::factory()->make());

        $listener = new StorePaymentRecord($repo);
        $listener->handle(new PaymentValidated(
            ['basket_id' => 'ORD-2', 'token' => 't'],
            ['transaction_id' => 'TXN-2']
        ));

        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
