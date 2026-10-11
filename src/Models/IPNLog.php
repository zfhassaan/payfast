<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * IPNLog Model — one row per inbound IPN delivery attempt.
 */
class IPNLog extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_RECEIVED = 'received';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_DUPLICATE = 'duplicate';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    protected $table = 'payfast_ipn_table';

    protected $fillable = [
        'uid',
        'idempotency_key',
        'payload_hash',
        'order_no',
        'transaction_id',
        'status',
        'processing_status',
        'duplicate_of',
        'amount',
        'currency',
        'details',
        'raw_payload',
        'request_headers',
        'ip_address',
        'received_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'details' => 'array',
        'request_headers' => 'array',
        'received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (IPNLog $log): void {
            if (empty($log->uid)) {
                $log->uid = (string) Str::uuid();
            }
            if (empty($log->processing_status)) {
                $log->processing_status = self::STATUS_RECEIVED;
            }
            if ($log->received_at === null) {
                $log->received_at = now();
            }
        });
    }
}
