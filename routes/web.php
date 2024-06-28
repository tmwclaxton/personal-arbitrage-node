<?php

use App\Http\Controllers\ProfileController;
use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\Transaction;
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
        $offer->payment_methods = implode(', ', json_decode($offer->payment_methods));

        // if offer is accepted find the transaction
        if ($offer->accepted) {
            $transaction = Transaction::where('offer_id', $offer->id)->first();
            $offer->transaction = $transaction;
        }
    }

    $btcFiats = BtcFiat::where('currency', 'USD')->orWhere('currency', 'GBP')->orWhere('currency', 'EUR')->get();

    $adminDashboard = AdminDashboard::all()->first();
    if (!$adminDashboard) {
        $adminDashboard = new AdminDashboard();
        $lightningNode = new LightningNode();
        $balanceArray = $lightningNode->getLightningWalletBalance();
        $adminDashboard->localBalance = $balanceArray['localBalance'];
        $adminDashboard->remoteBalance = $balanceArray['remoteBalance'];
        $adminDashboard->save();
    }

    return Inertia::render('Welcome', [
        'btcPrices' => $btcFiats,
        'offers' => $offers,
        'adminDashboard' => $adminDashboard
    ]);
})->name('welcome');

Route::post('/accept-offer', function () {
    $robosats = new Robosats();
    $offerId = request('offer_id');
    $offer = Offer::find($offerId);
    $response = $robosats->acceptOffer($offer->robosatsId);
    return redirect()->route('welcome');
})->name('accept-offer');

Route::post('/pay-bond', function () {
    $offerId = request('offer_id');
    $transaction = Transaction::where('offer_id', $offerId)->first();
    $invoice = $transaction->bond_invoice;
    $lightningNode = new LightningNode();
    $response = $lightningNode->payInvoice($invoice);
    return redirect()->route('welcome');
})->name('pay-bond');

Route::post('/pay-escrow', function () {
    // grab offer_id and transaction_id
    $offerId = request('offer_id');
    $transaction = Transaction::where('offer_id', $offerId)->first();
    $escrowInvoice = $transaction->escrow_invoice;
    // dd($escrowInvoice);
    $lightningNode = new LightningNode();
    $response = $lightningNode->payInvoice($escrowInvoice);
    return redirect()->route('welcome');
})->name('pay-escrow');


Route::post('/confirm-payment', function () {
    $offerId = request('offer_id');
    $offer = Offer::find($offerId);
    $transaction = Transaction::where('offer_id', $offerId)->first();
    $robosats = new Robosats();
    $response = $robosats->confirmReceipt($offer->robosatsId, $transaction);
    return redirect()->route('welcome');
})->name('confirm-payment');




Route::get('/testing', function () {
    //
    // $robosats = new Robosats();
    // $robosats->claimCompensation('', 'temple', '-----BEGIN PGP PRIVATE KEY BLOCK-----\n\nxYYEZnsonBYJKwYBBAHaRw8BAQdA50kwmUx1AunyYiukCXHcX8WKTcGbWhkC\nzmBV+anqoR7+CQMIDsjXel9rJMPg4OHL6eEQjTpKODKUb27/G5oEvcmsDxOn\nIaWg3kwZwpyLpDmXUVgWZEFqb6DLigqyCBc5K5I7NRroKr0ILZ8HQ3wHxZME\nXs1MUm9ib1NhdHMgSUQgOGY1ZTU4MmRjYzRiNjlkMjM2NjgxZjgzNGMxMGNj\nMmRiNWU0NjQwZmRiNzVhNDMzNTkwMWQ1NGIzMDA1MTRjM8KMBBAWCgA+BYJm\neyicBAsJBwgJkKypJt+M1B3PAxUICgQWAAIBAhkBApsDAh4BFiEE5ZaRtImc\nZ6D5bL2MrKkm34zUHc8AANkjAP99+0lYJYtLZJ5KsQVlOEE7MdDLdSuSOlpD\nE8y/HfgtkQEAqGWtPcTQBeVCadha47B5Qn7js2kbhpdAG62nqmadYA7HiwRm\neyicEgorBgEEAZdVAQUBAQdAX1L4Ldozcg1y6Pue5vvgFQR4lqGyZhpiGiEs\nA75M0F0DAQgH/gkDCOkwbVtNSDpW4FLyGxhtbMuhMOLyTTcf0bqVSGqLu5UU\njyDl0SUQYgRDACc2Gj49Pt7PO74f9MVBsbWcdewvd3P6KziHkAjCOvLK4o67\n4rvCeAQYFgoAKgWCZnsonAmQrKkm34zUHc8CmwwWIQTllpG0iZxnoPlsvYys\nqSbfjNQdzwAAb7oA/Rd4D3sXb6PKCPyplpb7gUmJ3SFOM6ui5PauEAQ36C7N\nAP9YYOBt9TdIsOZ5/VLc7kaXgLQZmqEKfRvaqMIiRT3UCw==\n=GLzY\n-----END PGP PRIVATE KEY BLOCK-----\n');

    // update all current transactions
    $transactions = Transaction::all();
    foreach ($transactions as $transaction) {
        $offer = $transaction->offer;
        $robosatsId = $offer->robosatsId;
        $robosats = new Robosats();
        $response = $robosats->updateTransactionStatus($robosatsId, $transaction->id);
    }
    return 'done';

    // // $response = $robosats->request('api/book/');
    // $response = $robosats->getBookOffers();
    //
    // $negativeBuyOffers = $robosats->getNegativePremiumBuyOffers($response['buyOffers'], '0');
    // $positiveSellOffers = $robosats->getPositivePremiumSellOffers($response['sellOffers'], '2');
    // return [
    //     'negativeBuyOffers' => $negativeBuyOffers,
    //     'positiveSellOffers' => $positiveSellOffers
    // ];

    // $lightningNode = new \App\WorkerClasses\LightningNode();
    // return $lightningNode->getPayments();
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


require __DIR__.'/auth.php';
