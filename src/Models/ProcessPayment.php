<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use zfhassaan\Payfast\Database\Factories\ProcessPaymentFactory;

/**
 * ProcessPayment Model
 *
 * @property int $id
 * @property string $uid
 * @property string|null $token
 * @property string|null $orderNo
 * @property string|null $data_3ds_secureid
 * @property string|null $data_3ds_pares
 * @property string|null $transaction_id
 * @property string $status
 * @property string|null $payment_method
 * @property string|null $payload
 * @property string|null $requestData
 * @property \Illuminate\Support\Carbon|null $otp_verified_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProcessPayment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payfast_process_payments_table';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'token',
        'orderNo',
        'data_3ds_secureid',
        'data_3ds_pares',
        'transaction_id',
        'status',
        'payment_method',
        'payload',
        'requestData',
        'otp_verified_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'otp_verified_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Payment status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_OTP_VERIFIED = 'otp_verified';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Payment method constants.
     */
    public const METHOD_CARD = 'card';
    public const METHOD_EASYPAISA = 'easypaisa';
    public const METHOD_JAZZCASH = 'jazzcash';
    public const METHOD_UPAISA = 'upaisa';

    /**
     * Check if payment is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment is validated.
     *
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    /**
     * Check if OTP is verified.
     *
     * @return bool
     */
    public function isOtpVerified(): bool
    {
        return $this->status === self::STATUS_OTP_VERIFIED;
    }

    /**
     * Check if payment is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark payment as validated.
     *
     * @return bool
     */
    public function markAsValidated(): bool
    {
        return $this->update(['status' => self::STATUS_VALIDATED]);
    }

    /**
     * Mark payment as OTP verified.
     *
     * @return bool
     */
    public function markAsOtpVerified(): bool
    {
        return $this->update([
            'status' => self::STATUS_OTP_VERIFIED,
            'otp_verified_at' => now(),
        ]);
    }

    /**
     * Mark payment as completed.
     *
     * @return bool
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed.
     *
     * @param string|null $reason
     * @return bool
     */
    public function markAsFailed(?string $reason = null): bool
    {
        $update = ['status' => self::STATUS_FAILED];
        if ($reason) {
            $payload = json_decode($this->payload ?? '{}', true);
            $payload['failure_reason'] = $reason;
            $update['payload'] = json_encode($payload);
        }

        return $this->update($update);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \zfhassaan\Payfast\Database\Factories\ProcessPaymentFactory::new();
    }
}
