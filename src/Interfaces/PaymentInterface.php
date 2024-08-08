<?php

namespace Interface\PaymentInterface;

interface PaymentInterface {
    public function getToken();
    public function refreshToken(string $token, string $refresh_token);
    public function initiateTransaction(array $data);
    public function walletTransaction(array $data);
    public function refundTransactionRequest(array $data);
    public function listBanks();
    public function listInstrumentsWithBank(string $code);
    public function getTransactionDetails(string $transactionId);
    public function validateWalletTransaction(array $data);
    public function getOtpScreen(array $data);
    public function  payWithEasyPaisa(array $data);
    public function  payWithUPaisa(array $data);
    public function  addPermenantPaymentInstrument(array $data);

}
