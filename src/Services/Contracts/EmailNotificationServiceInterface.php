<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services\Contracts;

use zfhassaan\Payfast\Models\ProcessPayment;

interface EmailNotificationServiceInterface
{
    /**
     * Send payment status notification email.
     *
     * @param ProcessPayment $payment
     * @param string $status
     * @param array<string, mixed> $data
     * @return void
     */
    public function sendPaymentStatusNotification(ProcessPayment $payment, string $status, array $data = []): void;

    /**
     * Send payment completion email to customer.
     *
     * @param ProcessPayment $payment
     * @param array<string, mixed> $transactionData
     * @return void
     */
    public function sendPaymentCompletionEmail(ProcessPayment $payment, array $transactionData = []): void;

    /**
     * Send payment completion email to admins.
     *
     * @param ProcessPayment $payment
     * @param array<string, mixed> $transactionData
     * @return void
     */
    public function sendAdminNotificationEmail(ProcessPayment $payment, array $transactionData = []): void;

    /**
     * Send payment failure email.
     *
     * @param ProcessPayment $payment
     * @param string $reason
     * @return void
     */
    public function sendPaymentFailureEmail(ProcessPayment $payment, string $reason): void;
}

