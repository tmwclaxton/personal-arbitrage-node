<?php

namespace App\Http\Controllers;

use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $btcFiats = BtcFiat::all();
        return Inertia::render('AdminDashboardIndex', [
            'adminDashboard' => AdminDashboard::all()->first(),
            'btcFiats' => $btcFiats,
            'paymentMethods' => PaymentMethod::all(),
            'currencies' => $btcFiats->pluck('currency'),
        ]);
    }

    public function addPaymentMethod(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:payment_methods',
            'handle' => 'required',
            'logo_url' => 'nullable',
            'specific_buy_premium' => 'nullable',
            'specific_sell_premium' => 'nullable',
        ]);

        PaymentMethod::create($request->all());

        return redirect()->route('dashboard.index');
    }

    public function updatePaymentMethod(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'handle' => 'required',
            'logo_url' => 'nullable',
            'specific_buy_premium' => 'nullable',
            'specific_sell_premium' => 'nullable',
        ]);

        $paymentMethod = PaymentMethod::find($id);
        $paymentMethod->update($request->all());

        return redirect()->route('dashboard.index');
    }

    public function deletePaymentMethod($id)
    {
        $paymentMethod = PaymentMethod::find($id);
        $paymentMethod->delete();

        return redirect()->route('dashboard.index');
    }
}
