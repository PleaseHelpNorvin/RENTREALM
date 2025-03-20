<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .receipt-container {
            width: 100%;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .header {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .details {
            margin-bottom: 15px;
        }
        .details p {
            margin: 5px 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f4f4f4;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            Payment Receipt
        </div>

        <div class="details">
            <p><strong>Receipt No:</strong> #{{ $payment->paymongo_payment_reference }}</p>
            <p><strong>Date:</strong> {{ $payment->created_at->format('F d, Y') }}</p>
            <p><strong>Paid By:</strong> {{ $user->name ?? 'N/A' }}</p>
            <p><strong>Billing ID:</strong> {{ $billing->id ?? 'N/A' }}</p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $billing->description ?? 'N/A' }}</td>
                    <td>${{ number_format($payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Thank you for your payment!</p>
        </div>
    </div>
</body>
</html>
