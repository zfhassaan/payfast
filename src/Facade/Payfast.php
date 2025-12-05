<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * PayFast Facade
 *
 * @method static \Illuminate\Http\JsonResponse getToken()
 * @method static \Illuminate\Http\JsonResponse|null refreshToken(string $token, string $refreshToken)
 * @method static \Illuminate\Http\JsonResponse getOTPScreen(array $data)
 * @method static \Illuminate\Http\JsonResponse listBanks()
 * @method static \Illuminate\Http\JsonResponse|bool listInstrumentsWithBank(string|array $code)
 * @method static \Illuminate\Http\JsonResponse getTransactionDetails(string $transactionId)
 * @method static bool|string refundTransactionRequest(array $data)
 * @method static mixed payWithEasyPaisa(array $data)
 * @method static mixed payWithUPaisa(array $data)
 * @method static string|bool validateWalletTransaction(array $data)
 * @method static bool|string walletTransactionInitiate(array $data)
 * @method static bool|string initiateTransaction(array $data)
 *
 * @see \zfhassaan\Payfast\PayFast
 */
class Payfast extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'payfast';
    }
}

