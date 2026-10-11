<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Repositories;

use Illuminate\Support\Collection;
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

    /**
     * @param  array<int, string>|null  $statuses
     * @return Collection<int, ProcessPayment>
     */
    public function findForStatusCheck(?array $statuses = null, int $limit = 50): Collection
    {
        $query = ProcessPayment::query();

        if ($statuses !== null && $statuses !== []) {
            $query->whereIn('status', $statuses);
        } else {
            $query->whereIn('status', [
                ProcessPayment::STATUS_PENDING,
                ProcessPayment::STATUS_VALIDATED,
                ProcessPayment::STATUS_OTP_VERIFIED,
            ]);
        }

        return $query->limit($limit)->get();
    }

    public function markAsCompleted(ProcessPayment $payment): bool
    {
        return $payment->markAsCompleted();
    }

    public function markAsFailed(ProcessPayment $payment, ?string $reason = null): bool
    {
        return $payment->markAsFailed($reason);
    }
}
