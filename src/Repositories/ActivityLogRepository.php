<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Repositories;

use zfhassaan\Payfast\Models\ActivityLog;
use zfhassaan\Payfast\Repositories\Contracts\ActivityLogRepositoryInterface;

class ActivityLogRepository implements ActivityLogRepositoryInterface
{
    /**
     * Create a new activity log entry.
     *
     * @param array<string, mixed> $data
     * @return ActivityLog
     */
    public function create(array $data): ActivityLog
    {
        return ActivityLog::create($data);
    }

    /**
     * Find activity log by transaction ID.
     *
     * @param string $transactionId
     * @return ActivityLog|null
     */
    public function findByTransactionId(string $transactionId): ?ActivityLog
    {
        return ActivityLog::where('transaction_id', $transactionId)->first();
    }

    /**
     * Find activity logs by order number.
     *
     * @param string $orderNo
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByOrderNo(string $orderNo)
    {
        return ActivityLog::where('order_no', $orderNo)->get();
    }

    /**
     * Find activity logs by user ID.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByUserId(int $userId)
    {
        return ActivityLog::where('user_id', $userId)->get();
    }

    /**
     * Find activity logs by status.
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByStatus(string $status)
    {
        return ActivityLog::where('status', $status)->get();
    }
}

