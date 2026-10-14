{{-- PDF layout for one payment receipt (plan §1.2 R9). Rendered by dompdf, not a web page. --}}
@php
    $reservation = $payment->reservation;
    $house = $reservation->house;
    $peso = fn (int $amount) => 'PHP '.number_format($amount);
    $types = ['reservation_fee' => 'Reservation fee', 'instalment' => 'Instalment', 'final' => 'Final payment'];
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        h1 { color: #c1121f; margin: 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        td.amount { text-align: right; }
        .total td { font-weight: bold; font-size: 14px; border-bottom: none; }
        .summary { margin-top: 20px; padding: 10px; background: #f3f4f6; }
        .note { margin-top: 24px; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Luminest</h1>
    <p class="muted">Official receipt</p>

    <p>
        <strong>Receipt no.:</strong> {{ $payment->receipt_number }}<br>
        <strong>Date:</strong> {{ $payment->paid_at->format('F j, Y g:i A') }}<br>
        <strong>Buyer:</strong> {{ $reservation->user->name }} ({{ $reservation->user->email }})
    </p>

    <p><strong>For:</strong> {{ $house->houseType->name }}, Block {{ $house->block }} Lot {{ $house->lot }}</p>

    <table>
        <tr><td>Payment type</td><td class="amount">{{ $types[$payment->type->value] }}</td></tr>
        <tr><td>Total price</td><td class="amount">{{ $peso($reservation->total_price) }}</td></tr>
        <tr><td>Total paid to date</td><td class="amount">{{ $peso($reservation->total_price - $payment->balance_after) }}</td></tr>
        <tr class="total"><td>Amount paid now</td><td class="amount">{{ $peso($payment->amount) }}</td></tr>
        <tr class="total"><td>Remaining balance</td><td class="amount">{{ $peso($payment->balance_after) }}</td></tr>
    </table>

    <p class="summary">
        {{ $house->houseType->name }} — Paid {{ $peso($payment->amount) }} | Balance {{ $peso($payment->balance_after) }} | {{ $payment->paid_at->toDateString() }}
    </p>

    <p class="note muted">Simulated payment. No real transaction took place.</p>
</body>
</html>
