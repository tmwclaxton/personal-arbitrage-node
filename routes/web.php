<?php

use App\Http\Controllers\ProfileController;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\WorkerClasses\LightningNode;
use App\WorkerClasses\Robosats;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $offers = Offer::all();
    // change the expires_at to a human readable format
    foreach ($offers as $offer) {
        $offer->expires_at = Carbon::parse($offer->expires_at)->diffForHumans();
        // round amount to 2 decimal places
        $offer->amount = number_format($offer->amount, 2);
        // round min_amount to 2 decimal places and max amount to 2 decimal places
        $offer->min_amount = number_format($offer->min_amount, 2);
        $offer->max_amount = number_format($offer->max_amount, 2);
        // add a percentage to the premium
        $offer->premium = $offer->premium . '%';
        // json decode the payment methods so it is a space separated string
        $offer->payment_methods = implode(' ', json_decode($offer->payment_methods));
    }

    $btcFiats = BtcFiat::where('currency', 'USD')->orWhere('currency', 'GBP')->orWhere('currency', 'EUR')->get();


    return Inertia::render('Welcome', [
        'btcPrices' => $btcFiats,
        'offers' => $offers
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');



Route::get('/testing', function () {

    $robosats = new Robosats();
    $prices = $robosats->getCurrentPrices();
    foreach ($prices as $price) {
        $btcFiat = new \App\Models\BtcFiat();
        // if the currency is already in the database, update it
        $btcFiat->updateOrCreate(
            ['currency' => $price['code']],
            ['price' => $price['price']]
        );
    }
    // // $response = $robosats->request('api/book/');
    // $response = $robosats->getBookOffers();
    //
    // $negativeBuyOffers = $robosats->getNegativePremiumBuyOffers($response['buyOffers'], '0');
    // $positiveSellOffers = $robosats->getPositivePremiumSellOffers($response['sellOffers'], '2');
    // return [
    //     'negativeBuyOffers' => $negativeBuyOffers,
    //     'positiveSellOffers' => $positiveSellOffers
    // ];

    $lightningNode = new \App\WorkerClasses\LightningNode();
    return $lightningNode->getPayments();
    // return $lightningNode->getInvoiceDetails('lnbc20u1pn8mezjpp5ghwgackp9gtmchlptfgsvuafrntpmep90zvm2xh32g5jlt5jk6rqdqqcqzzgxqyz5vqrzjqwnvuc0u4txn35cafc7w94gxvq5p3cu9dd95f7hlrh0fvs46wpvhdesygxzrj2w2tgqqqqryqqqqthqqpysp53wh2jg6k83kdntaelutzdxtxwxnkevszdec6p0gg0ggk52ds2w0q9qrsgq8ctexfelzrn5tdhh53nertza4zufms482stn0cwmzqz7dqx0phpkrxp0psk75v2cfjdey3sx9cl5eyqcvfjrcyxwqmp877s2pjpq5hqpx009p0');
});

Route::get('/home2', function () {

    $robosats = new Robosats();
    // $response = $robosats->request('api/book/');
    $response = $robosats->getBookOffers();

    $negativeBuyOffers = $robosats->getNegativePremiumBuyOffers($response['buyOffers'], '0');
    $positiveSellOffers = $robosats->getPositivePremiumSellOffers($response['sellOffers'], '2');

    return view('welcome', [
        'negativeBuyOffers' => $negativeBuyOffers,
        'positiveSellOffers' => $positiveSellOffers
    ]);
});

Route::post('/initiateBuyOffers', function () {

});


require __DIR__.'/auth.php';
