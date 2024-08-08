<?php

namespace zfhassaan\Payfast;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use zfhassaan\Payfast\Payment;
use zfhassaan\Payfast\helper\Utility;
use zfhassaan\Payfast\Models\ProcessPayment;
use Illuminate\Support\Facades\Validator;
use zfhassaan\Payfast\Services\PayFastService;

/**
 * This section contains the details of all APIs provided by PAYFAST. The merchants, acquirers and/or
 * aggregators could call these APIs. These API\’S are based on REST architecture and serve standard HTTP
 * codes for the response payload.
 * @method getip()
 */
class PayFast
{

    protected $payfastService;

    public function __construct(PayFastService $payfastService)
    {
        $this->payfastService = $payfastService;
    }

    public function getToken(): JsonResponse
    {
        return $this->payfastService->getToken();
    }

    public function refreshToken(string $token, string $refresh_token): JsonResponse
    {
        return $this->payfastService->refreshToken($token, $refresh_token);
    }

    public function getOtpScreen($data): JsonResponse
    {
        return $this->payfastService->getOtpScreen($data);
    }

    public function listBanks(): JsonResponse
    {
        return $this->payfastService->listBanks();
    }
    
    public function listInstrumentsWithBank(string $code): JsonResponse
    {
        return $this->payfastService->listInstrumentsWithBank($code);
    }

    public function getTransactionDetails(string $transactionId): JsonResponse
    {
        return $this->payfastService->getTransactionDetails($transactionId);
    }

    public function refundTransactionRequest(array $data): bool|string
    {
        return $this->payfastService->refundTransactionRequest($data);
    }

    public function payWithEasyPaisa(array $data): bool|string
    {
        return $this->payfastService->payWithEasyPaisa($data);
    }

    public function payWithUPaisa(array $data): bool|string
    {
        return $this->payfastService->payWithUPaisa($data);
    }

    public function initiateTransaction(array $data): bool|string
    {
        return $this->payfastService->initiateTransaction($data);
    }

    public function addPermenantPaymentInstrument(array $data): JsonResponse
    {
        return $this->payfastService->addPermenantPaymentInstrument($data);
    }


}
