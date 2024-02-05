<?php

namespace zfhassaan\Payfast\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $options)
 */
class ProcessPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'token','orderNo','data_3ds_secureid','transaction_id','payload','requestData'
    ];
}
