<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

class ConfigService
{
    private string $apiUrl;
    private string $merchantId;
    private string $securedKey;
    private string $grantType;
    private string $returnUrl;
    private string $checkoutUrl;
    private string $storeId;
    private string $mode;

    public function __construct()
    {
        $this->load();
    }

    /**
     * Load configuration from config file.
     *
     * @return void
     */
    private function load(): void
    {
        $this->mode = config('payfast.mode', 'sandbox');
        $this->apiUrl = $this->mode === 'sandbox'
            ? config('payfast.sandbox_api_url', '')
            : config('payfast.api_url', '');
        $this->merchantId = config('payfast.merchant_id', '');
        $this->securedKey = config('payfast.secured_key', '');
        $this->grantType = config('payfast.grant_type', '');
        $this->returnUrl = config('payfast.return_url', '');
        $this->storeId = config('payfast.store_id', '');
        $this->checkoutUrl = config('payfast.checkout_url', '');
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getSecuredKey(): string
    {
        return $this->securedKey;
    }

    public function getGrantType(): string
    {
        return $this->grantType;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function getStoreId(): string
    {
        return $this->storeId;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Get checkout URL for IPN (Instant Payment Notification) callbacks.
     *
     * If not explicitly configured via PAYFAST_CHECKOUT_URL, this will
     * auto-resolve to the package's built-in IPN route.
     *
     * @return string
     */
    public function getCheckoutUrl(): string
    {
        if (!empty($this->checkoutUrl)) {
            return $this->checkoutUrl;
        }

        // Auto-resolve to the package's built-in IPN route
        try {
            return route('payfast.ipn.handle');
        } catch (\Exception $e) {
            return '';
        }
    }
}
