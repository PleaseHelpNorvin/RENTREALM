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
            margin: 0;
            padding: 20px;
        }
        .receipt-container {
            width: 100%;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .company-details {
            text-align: center;
            font-size: 12px;
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
        .qr-code {
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            {{ $receipt_title}} Receipt
        </div>

        <div class="company-details">
            <p><strong>Rent Realm</strong></p>
            {{-- <p>123 Business Street, City, Country</p> --}}
            <p>Email: rentrealm@please_pasara_nami.com | Phone: +9454365069</p>
        </div>

        <div class="details">
            <p><strong>Receipt No:</strong> #{{ $payment->paymongo_payment_reference }}</p>
            <p><strong>Date:</strong> {{ $payment->created_at->format('F d, Y') }}</p>
            <p><strong>Paid By:</strong> {{ $user->name ?? 'N/A' }}</p>
            <p><strong>Billing ID:</strong> {{ $billing->id ?? 'N/A' }}</p>
            <p><strong>Billing Period:</strong> {{ $billing->billing_month->format('F Y') }}</p>
            <p><strong>Payment Status:</strong> {{ ucfirst($payment->status) }}</p>
            <p><strong>Payment Method:</strong> {{ $payment->payment_method }}</p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount Due</th>
                    <th>Amount Paid</th>
                    <th>Remaining Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $billing->billing_title }}</td>
                    <td>${{ number_format($billing->total_amount, 2) }}</td>
                    <td>${{ number_format($payment->amount_paid / 100, 2) }}</td>
                    <td>${{ number_format($payment->remaining_balance, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- <div class="qr-code">
            <img src="data:image/png;base64,{{ base64_encode(QrCode::size(100)->generate(url('/verify-payment/' . $payment->id))) }}" alt="QR Code">
            <p>Scan to verify this payment</p>
        </div> --}}

        <div class="footer">
            <p>Thank you for your payment!</p>
        </div>
    </div>
</body>
</html>
