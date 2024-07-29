<?php

namespace App\Http\Controllers;

use App\Models\AdminDashboard;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $offerController = new OfferController();
        $getInfo = $offerController->getInfo();
        $btcFiats = $getInfo['btcFiats'];
        return Inertia::render('AdminDashboardIndex', [
            'adminDashboard' => AdminDashboard::all()->first(),
            'btcFiats' => $btcFiats
        ]);
    }
}
