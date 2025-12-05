<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Repositories;

use zfhassaan\Payfast\Models\IPNLog;
use zfhassaan\Payfast\Repositories\Contracts\IPNLogRepositoryInterface;

class IPNLogRepository implements IPNLogRepositoryInterface
{
    /**
     * Create a new IPN log entry.
     *
     * @param array<string, mixed> $data
     * @return IPNLog
     */
    public function create(array $data): IPNLog
    {
        return IPNLog::create($data);
    }

    /**
     * Find IPN log by transaction ID.
     *
     * @param string $transactionId
     * @return IPNLog|null
     */
    public function findByTransactionId(string $transactionId): ?IPNLog
    {
        return IPNLog::where('transaction_id', $transactionId)->first();
    }

    /**
     * Find IPN logs by order number.
     *
     * @param string $orderNo
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByOrderNo(string $orderNo): \Illuminate\Database\Eloquent\Collection
    {
        return IPNLog::where('order_no', $orderNo)->get();
    }
}


