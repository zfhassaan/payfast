<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Listeners;

use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;
use zfhassaan\Payfast\Services\Contracts\EmailNotificationServiceInterface;

class SendPaymentEmailNotifications
{
    public function __construct(
        private readonly EmailNotificationServiceInterface $emailNotificationService,
        private readonly ProcessPaymentRepositoryInterface $paymentRepository
    ) {
    }

    /**
     * Handle payment validated event.
     *
     * @param PaymentValidated $event
     * @return void
     */
    public function handlePaymentValidated(PaymentValidated $event): void
    {
        $payment = $this->findPaymentByTransactionId($event->validationResponse['transaction_id'] ?? null);

        if ($payment) {
            $this->emailNotificationService->sendPaymentStatusNotification(
                $payment,
                ProcessPayment::STATUS_VALIDATED,
                $event->validationResponse
            );
        }
    }

    /**
     * Handle payment completed event.
     *
     * @param PaymentCompleted $event
     * @return void
     */
    public function handlePaymentCompleted(PaymentCompleted $event): void
    {
        $payment = $this->findPaymentByTransactionData($event->transactionData);

        if ($payment) {
            // Send to customer
            $this->emailNotificationService->sendPaymentCompletionEmail($payment, $event->response);

            // Send to admins
            $this->emailNotificationService->sendAdminNotificationEmail($payment, $event->response);
        }
    }

    /**
     * Handle payment failed event.
     *
     * @param PaymentFailed $event
     * @return void
     */
    public function handlePaymentFailed(PaymentFailed $event): void
    {
        $payment = $this->findPaymentByTransactionData($event->paymentData);

        if ($payment) {
            $this->emailNotificationService->sendPaymentFailureEmail($payment, $event->errorMessage);
        }
    }

    /**
     * Find payment by transaction ID.
     *
     * @param string|null $transactionId
     * @return ProcessPayment|null
     */
    private function findPaymentByTransactionId(?string $transactionId): ?ProcessPayment
    {
        if (!$transactionId) {
            return null;
        }

        return $this->paymentRepository->findByTransactionId($transactionId);
    }

    /**
     * Find payment by transaction data.
     *
     * @param array<string, mixed> $transactionData
     * @return ProcessPayment|null
     */
    private function findPaymentByTransactionData(array $transactionData): ?ProcessPayment
    {
        if (isset($transactionData['transaction_id'])) {
            return $this->paymentRepository->findByTransactionId($transactionData['transaction_id']);
        }

        if (isset($transactionData['orderNumber']) || isset($transactionData['basket_id'])) {
            $orderNo = $transactionData['orderNumber'] ?? $transactionData['basket_id'];
            return $this->paymentRepository->findByBasketId($orderNo);
        }

        return null;
    }
}


