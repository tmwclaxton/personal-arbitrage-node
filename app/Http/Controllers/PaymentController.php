<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index()
    {
        $payment = Payment::query()->orderByDesc('created_at');
        return Inertia::render('PaymentsIndex', [
            'payments' => $payment->paginate(25)->setPath(route('payments.index'))->through(fn($purchase)=>[
                "Payment ID" => $purchase->platform_transaction_id,
                "Method" => $purchase->payment_method,
                "Reference" => $purchase->payment_reference,
                "Currency" => $purchase->payment_currency,
                "Amount" => $purchase->payment_amount,
                "Description" => $purchase->platform_description,
                "Paid At" => $purchase->payment_date,
                "Record Creation" => $purchase->created_at,
            ])->withQueryString(),
        ]);
    }
}
