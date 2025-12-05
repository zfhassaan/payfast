<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Repositories\Contracts;

use zfhassaan\Payfast\Models\IPNLog;

interface IPNLogRepositoryInterface
{
    /**
     * Create a new IPN log entry.
     *
     * @param array<string, mixed> $data
     * @return IPNLog
     */
    public function create(array $data): IPNLog;

    /**
     * Find IPN log by transaction ID.
     *
     * @param string $transactionId
     * @return IPNLog|null
     */
    public function findByTransactionId(string $transactionId): ?IPNLog;

    /**
     * Find IPN logs by order number.
     *
     * @param string $orderNo
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByOrderNo(string $orderNo);
}


