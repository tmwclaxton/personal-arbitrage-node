<?php

use App\WorkerClasses\Robosats;
use HeadlessChromium\BrowserFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $robosats = new Robosats();
    // $response = $robosats->request('api/book/');
    $response = $robosats->getBookOffers();

    $negativeBuyOffers = $robosats->getNegativePremiumBuyOffers($response['buyOffers'], '-0.05');
    $positiveSellOffers = $robosats->getPositivePremiumSellOffers($response['sellOffers'], '0.05');
    return [
        'negativeBuyOffers' => $negativeBuyOffers,
        'positiveSellOffers' => $positiveSellOffers
    ];
});
