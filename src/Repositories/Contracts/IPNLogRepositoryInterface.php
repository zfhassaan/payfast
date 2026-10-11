<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Repositories\Contracts;

use Illuminate\Support\Collection;
use zfhassaan\Payfast\Models\IPNLog;

interface IPNLogRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): IPNLog;

    public function findById(int|string $id): ?IPNLog;

    public function findByTransactionId(string $transactionId): ?IPNLog;

    /**
     * @return Collection<int, IPNLog>
     */
    public function findByOrderNo(string $orderNo): Collection;

    public function findProcessedByIdempotencyKey(string $idempotencyKey): ?IPNLog;

    public function findProcessedByTransactionId(string $transactionId): ?IPNLog;

    /**
     * @param array<string, mixed> $data
     */
    public function update(IPNLog $log, array $data): bool;
}
