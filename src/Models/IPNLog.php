<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * IPNLog Model
 *
 * Stores Instant Payment Notification (IPN) records from PayFast.
 *
 * @property int $id
 * @property string $uid
 * @property string|null $order_no
 * @property string $transaction_id
 * @property string $status
 * @property float $amount
 * @property string $currency
 * @property array|null $details
 * @property \Illuminate\Support\Carbon $received_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class IPNLog extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payfast_ipn_table';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'order_no',
        'transaction_id',
        'status',
        'amount',
        'currency',
        'details',
        'received_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'details' => 'array',
        'received_at' => 'datetime',
    ];
}


