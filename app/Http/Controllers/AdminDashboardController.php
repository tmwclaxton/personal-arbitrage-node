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
            'paymentMethods' => PaymentMethod::orderByDesc('handle')->orderByDesc('custom_message')->orderByDesc('logo_url')->get(),
            'paymentMethodList' => PaymentMethod::all()->pluck('name'),
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
            'allowed_currencies' => 'array|nullable',
            'ask_for_reference' => 'boolean|nullable',
            'custom_message' => 'nullable',
        ]);
        // convert array to json
        $request->merge(['allowed_currencies' => json_encode($request->allowed_currencies)]);

        PaymentMethod::create($request->all());

        return redirect()->route('dashboard.index');
    }

    public function updatePaymentMethod(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'handle' => 'nullable',
            'logo_url' => 'nullable',
            'specific_buy_premium' => 'nullable',
            'specific_sell_premium' => 'nullable',
            'allowed_currencies' => 'array|nullable',
            'ask_for_reference' => 'boolean|nullable',
            'custom_message' => 'nullable',
        ]);
        // convert array to json
        $request->merge(['allowed_currencies' => json_encode($request->allowed_currencies)]);

        $paymentMethod = PaymentMethod::find($id);
        $paymentMethod->update($request->all());
        return ['message' => 'Payment method updated'];
    }

    public function deletePaymentMethod($id)
    {
        $paymentMethod = PaymentMethod::find($id);
        $paymentMethod->delete();

        return redirect()->route('dashboard.index');
    }
}
