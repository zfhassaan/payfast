<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ActivityLog Model
 *
 * Tracks all payment activities and transactions for audit purposes.
 *
 * @property int $id
 * @property string $uid
 * @property int $user_id
 * @property string $transaction_id
 * @property string $order_no
 * @property string $status
 * @property float $amount
 * @property string|null $details
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon $transaction_date
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ActivityLog extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payfast_activity_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'user_id',
        'transaction_id',
        'order_no',
        'status',
        'amount',
        'details',
        'metadata',
        'transaction_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'transaction_date' => 'datetime',
    ];
}


