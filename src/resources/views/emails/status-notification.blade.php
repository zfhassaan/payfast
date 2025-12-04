<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status Update</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f4f4f4; padding: 20px; border-radius: 5px;">
        <h2 style="color: #333; margin-top: 0;">Payment Status Update</h2>
        
        <p>Dear Customer,</p>
        
        <p>Your payment status has been updated:</p>
        
        <div style="background-color: #fff; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Order Number:</strong> {{ $payment->orderNo ?? 'N/A' }}</p>
            <p><strong>Transaction ID:</strong> {{ $payment->transaction_id ?? 'N/A' }}</p>
            <p><strong>Status:</strong> <span style="text-transform: uppercase; font-weight: bold;">{{ $status }}</span></p>
            @if(isset($data['amount']))
                <p><strong>Amount:</strong> {{ number_format($data['amount'], 2) }} PKR</p>
            @endif
        </div>
        
        <p>Thank you for your business.</p>
        
        <p style="margin-top: 30px; font-size: 12px; color: #666;">
            This is an automated email. Please do not reply to this message.
        </p>
    </div>
</body>
</html>

