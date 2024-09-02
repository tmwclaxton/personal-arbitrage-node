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
}
