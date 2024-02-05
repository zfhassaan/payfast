<?php

namespace zfhassaan\Payfast\Interfaces;

interface PaymentInterface {
    public function GetPayfastToken($fields);
    public function GetToken();
    public function RefreshToken(String $token, String $refresh_token);
    public function GetOTPScreen($data);
    public function ListBanks();
    public function ListInstrumentsWithBank(String $code);
    public function GetTransactionDetails(String $transactionId);
    public function RefundTransactionRequest(Array $data);
    public function PayWithEasyPaisa(Array $data);
    public function PayWithUPaisa(Array $data);
    public function ValidateWalletTransaction(Array $data);
    public function WalletTransactionInitiate(Array $data);
    public function InitiateTransaction(Array $data);
}
