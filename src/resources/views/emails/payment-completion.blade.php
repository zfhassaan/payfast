<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Completed Successfully</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f4f4f4; padding: 20px; border-radius: 5px;">
        <h2 style="color: #28a745; margin-top: 0;">Payment Completed Successfully</h2>
        
        <p>Dear Customer,</p>
        
        <p>We are pleased to inform you that your payment has been completed successfully.</p>
        
        <div style="background-color: #fff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;">
            <p><strong>Order Number:</strong> {{ $payment->orderNo ?? 'N/A' }}</p>
            <p><strong>Transaction ID:</strong> {{ $payment->transaction_id ?? 'N/A' }}</p>
            <p><strong>Payment Method:</strong> {{ ucfirst($payment->payment_method ?? 'N/A') }}</p>
            @if(isset($transactionData['amount']))
                <p><strong>Amount:</strong> {{ number_format($transactionData['amount'], 2) }} PKR</p>
            @endif
            <p><strong>Completed At:</strong> {{ $payment->completed_at ? $payment->completed_at->format('Y-m-d H:i:s') : 'N/A' }}</p>
        </div>
        
        <p>Your order is now being processed. You will receive further updates shortly.</p>
        
        <p>Thank you for your business!</p>
        
        <p style="margin-top: 30px; font-size: 12px; color: #666;">
            This is an automated email. Please do not reply to this message.
        </p>
    </div>
</body>
</html>


