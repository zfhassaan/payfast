<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Repositories;

use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;

class ProcessPaymentRepository implements ProcessPaymentRepositoryInterface
{
    /**
     * Create a new payment record.
     *
     * @param array<string, mixed> $data
     * @return ProcessPayment
     */
    public function create(array $data): ProcessPayment
    {
        return ProcessPayment::create($data);
    }

    /**
     * Find a payment by transaction ID.
     *
     * @param string $transactionId
     * @return ProcessPayment|null
     */
    public function findByTransactionId(string $transactionId): ?ProcessPayment
    {
        return ProcessPayment::where('transaction_id', $transactionId)->first();
    }

    /**
     * Find a payment by basket ID.
     *
     * @param string $basketId
     * @return ProcessPayment|null
     */
    public function findByBasketId(string $basketId): ?ProcessPayment
    {
        return ProcessPayment::where('orderNo', $basketId)->first();
    }

    /**
     * Update a payment record.
     *
     * @param string $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        return ProcessPayment::where('id', $id)->update($data) > 0;
    }

    /**
     * Find a payment by pares.
     *
     * @param string $pares
     * @return ProcessPayment|null
     */
    public function findByPares(string $pares): ?ProcessPayment
    {
        return ProcessPayment::where('data_3ds_pares', $pares)->first();
    }
}

