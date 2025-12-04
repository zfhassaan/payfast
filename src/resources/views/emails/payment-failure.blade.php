<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f4f4f4; padding: 20px; border-radius: 5px;">
        <h2 style="color: #dc3545; margin-top: 0;">Payment Failed</h2>
        
        <p>Dear Customer,</p>
        
        <p>We regret to inform you that your payment could not be processed.</p>
        
        <div style="background-color: #fff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc3545;">
            <p><strong>Order Number:</strong> {{ $payment->orderNo ?? 'N/A' }}</p>
            <p><strong>Transaction ID:</strong> {{ $payment->transaction_id ?? 'N/A' }}</p>
            <p><strong>Reason:</strong> {{ $reason }}</p>
        </div>
        
        <p>Please try again or contact our support team if you need assistance.</p>
        
        <p>Thank you for your understanding.</p>
        
        <p style="margin-top: 30px; font-size: 12px; color: #666;">
            This is an automated email. Please do not reply to this message.
        </p>
    </div>
</body>
</html>

