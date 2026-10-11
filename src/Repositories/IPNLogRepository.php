<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Repositories;

use Illuminate\Support\Collection;
use zfhassaan\Payfast\Models\IPNLog;
use zfhassaan\Payfast\Repositories\Contracts\IPNLogRepositoryInterface;

class IPNLogRepository implements IPNLogRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): IPNLog
    {
        return IPNLog::create($data);
    }

    public function findById(int|string $id): ?IPNLog
    {
        return IPNLog::find($id);
    }

    public function findByTransactionId(string $transactionId): ?IPNLog
    {
        return IPNLog::where('transaction_id', $transactionId)->latest('id')->first();
    }

    public function findByOrderNo(string $orderNo): Collection
    {
        return IPNLog::where('order_no', $orderNo)->get();
    }

    public function findProcessedByIdempotencyKey(string $idempotencyKey): ?IPNLog
    {
        return IPNLog::where('idempotency_key', $idempotencyKey)
            ->where('processing_status', IPNLog::STATUS_PROCESSED)
            ->first();
    }

    public function findProcessedByTransactionId(string $transactionId): ?IPNLog
    {
        return IPNLog::where('transaction_id', $transactionId)
            ->where('processing_status', IPNLog::STATUS_PROCESSED)
            ->first();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(IPNLog $log, array $data): bool
    {
        return $log->update($data);
    }
}
