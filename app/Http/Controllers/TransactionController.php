<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::query()->orderByDesc('created_at');

        return Inertia::render('TransactionsIndex', [
            'transactions' => $transactions->paginate(25)->setPath(route('transactions.index'))->through(fn($transaction)=>[
                "Offer ID" => $transaction->offer_id,
                "Bond Invoice" => $transaction->bond_invoice,
                "Escrow Invoice" => $transaction->escrow_invoice,
                "Status" => $transaction->status_message,
                "Satoshi Fees (inc Coordinator)" => $transaction->fees,
                "Created At" => $transaction->created_at,
            ])->withQueryString(),
        ]);
    }
}
