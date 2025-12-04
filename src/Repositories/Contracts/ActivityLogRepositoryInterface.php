<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Repositories\Contracts;

use zfhassaan\Payfast\Models\ActivityLog;

interface ActivityLogRepositoryInterface
{
    /**
     * Create a new activity log entry.
     *
     * @param array<string, mixed> $data
     * @return ActivityLog
     */
    public function create(array $data): ActivityLog;

    /**
     * Find activity log by transaction ID.
     *
     * @param string $transactionId
     * @return ActivityLog|null
     */
    public function findByTransactionId(string $transactionId): ?ActivityLog;

    /**
     * Find activity logs by order number.
     *
     * @param string $orderNo
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByOrderNo(string $orderNo);

    /**
     * Find activity logs by user ID.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByUserId(int $userId);

    /**
     * Find activity logs by status.
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByStatus(string $status);
}

