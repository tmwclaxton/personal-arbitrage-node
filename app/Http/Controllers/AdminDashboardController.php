<?php

namespace App\Http\Controllers;

use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\PaymentMethod;
use App\WorkerClasses\Robosats;
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
            'paymentMethods' => PaymentMethod::orderByDesc('preference', 'desc')
                ->orderByDesc('handle')
                ->orderByDesc('custom_buy_message')
                ->orderByDesc('custom_sell_message')
                ->orderByDesc('logo_url')->get(),
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
            'custom_buy_message' => 'nullable',
            'custom_sell_message' => 'nullable',
            'preference' => 'integer|nullable',
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
            'custom_buy_message' => 'nullable',
            'custom_sell_message' => 'nullable',
            'preference' => 'integer|nullable',
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

    public function panic(): void
    {
        // if panic button has been pressed pause all offers
        $offers = Offer::where('status', '=', 1)->get();
        foreach ($offers as $offer) {
            $robosats = new Robosats();
            $robosats->togglePauseOffer($offer);
        }

    }

    public function calm(): void
    {
        // unpause all offers
        $offers = Offer::where('status', '=', 2)->get();
        foreach ($offers as $offer) {
            $robosats = new Robosats();
            $robosats->togglePauseOffer($offer);
        }
    }
}
