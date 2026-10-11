<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Repositories\Contracts;

use Illuminate\Support\Collection;
use zfhassaan\Payfast\Models\ProcessPayment;

interface ProcessPaymentRepositoryInterface
{
    /**
     * Create a new payment record.
     *
     * @param array<string, mixed> $data
     * @return ProcessPayment
     */
    public function create(array $data): ProcessPayment;

    /**
     * Find a payment by transaction ID.
     *
     * @param string $transactionId
     * @return ProcessPayment|null
     */
    public function findByTransactionId(string $transactionId): ?ProcessPayment;

    /**
     * Find a payment by basket ID.
     *
     * @param string $basketId
     * @return ProcessPayment|null
     */
    public function findByBasketId(string $basketId): ?ProcessPayment;

    /**
     * Update a payment record.
     *
     * @param string $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(string $id, array $data): bool;

    /**
     * Find a payment by pares.
     *
     * @param string $pares
     * @return ProcessPayment|null
     */
    public function findByPares(string $pares): ?ProcessPayment;

    /**
     * Find payments for status reconciliation (console command).
     *
     * @param  array<int, string>|null  $statuses  Null = pending/validated/otp_verified
     * @return Collection<int, ProcessPayment>
     */
    public function findForStatusCheck(?array $statuses = null, int $limit = 50): Collection;

    /**
     * Mark payment completed via model helper.
     */
    public function markAsCompleted(ProcessPayment $payment): bool;

    /**
     * Mark payment failed via model helper.
     */
    public function markAsFailed(ProcessPayment $payment, ?string $reason = null): bool;
}

