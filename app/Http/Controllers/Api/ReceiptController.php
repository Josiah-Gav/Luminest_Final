<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * GET /api/payments/{payment}/receipt: the PDF receipt of one payment (R9),
 * built on demand from the payments row, so it can be downloaded any time.
 */
class ReceiptController extends Controller
{
    /** GET /api/payments/{id}/receipt: streams the PDF; only the payer, a property manager or an admin (ReservationPolicy::downloadReceipt). */
    public function __invoke(Payment $payment): Response
    {
        $payment->load('reservation.user', 'reservation.house.houseType');
        Gate::authorize('downloadReceipt', $payment->reservation);

        return Pdf::loadView('pdf.receipt', ['payment' => $payment])
            ->download("{$payment->receipt_number}.pdf");
    }
}
