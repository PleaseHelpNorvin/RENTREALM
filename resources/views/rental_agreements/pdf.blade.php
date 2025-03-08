<!DOCTYPE html>
<html>
<head>
    <title>Rental Agreement</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .contract-header { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        .signature-container { margin-top: 20px; text-align: left; }
        .signature-container img { width: 200px; height: auto; }
    </style>
</head>
<body>
    <div class="contract-header">Rental Agreement Contract</div>

    <div class="details">
        <p><strong>Agreement Code:</strong> {{ $rentalAgreement->agreement_code ?? 'N/A' }}</p>
        <p><strong>Rent Start Date:</strong> {{ $rentalAgreement->rent_start_date ?? 'N/A' }}</p>
        <p><strong>Rent End Date:</strong> {{ $rentalAgreement->rent_end_date ?? 'N/A' }}</p>
        <p><strong>Total Monthly Due:</strong> {{ $rentalAgreement->total_monthly_due ?? 'N/A' }}</p>
        <p><strong>Person Count:</strong> {{ $rentalAgreement->person_count ?? 'N/A' }}</p>
        <p><strong>Status:</strong> {{ $rentalAgreement->status ?? 'N/A' }}</p>
    </div>

    <div class="signature-container">
        <h2>Signature</h2>
        @if (!empty($signatureImage))
            <img src="{{ $signatureImage }}" alt="Signature">
        @else
            <p>No signature available</p>
        @endif
    </div>
</body>
</html>
