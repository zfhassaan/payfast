<?php

namespace Interface\PaymentInterface;

interface PaymentInterface {
    public function getToken();
    public function refreshToken();
    public function customer_validate($data);
    public function wallet($data);
    public function initiate_transaction($data);
    public function list_banks();
    public function payment_instrument_type($data);
    public function issuer_bank_instrument_id($data);
}
