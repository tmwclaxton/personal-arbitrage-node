<?php

use App\Console\Commands\UpdateOffers;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProfileController;
use App\Jobs\ConfirmPayment;
use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\Robot;
use App\Models\Transaction;
use App\Services\DiscordService;
use App\Services\PgpService;
use App\WorkerClasses\LightningNode;
use App\WorkerClasses\Robosats;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\DiscordAlerts\Facades\DiscordAlert;


Route::post('/updateAdminDashboard', function () {
    $adminDashboard = AdminDashboard::all()->first();
// dump all the keys
//             dd(request()->adminDashboard["payment_methods"]);
    // iterate through the request and update the admin dashboard
    foreach (request()->adminDashboard as $key => $value) {
        // check if the key is in the admin dashboard
        // if not payment methods then update
        if (isset($adminDashboard->$key)) {
            if ($key !== "payment_methods") {
                $adminDashboard->$key = $value;
            } else {
                $adminDashboard->$key = json_encode($value);
            }
        }
    }
    // set the payment methods separately
    $adminDashboard->payment_methods = json_encode(request()->adminDashboard["payment_methods"]);
    $adminDashboard->save();
    // set payment currencies separately
    $adminDashboard->payment_currencies = json_encode(request()->adminDashboard["payment_currencies"]);
    $adminDashboard->save();
    return $adminDashboard;
})->name('updateAdminDashboard');



Route::get('/', [\App\Http\Controllers\OfferController::class, 'index'])->name('welcome');
Route::get('/offers', [\App\Http\Controllers\OfferController::class, 'getOffers'])->name('offers.index');


Route::post('/create-robot', function () {
    $offerId = request('offer_id');
    $offer = Offer::find($offerId);

    $robosats = new Robosats();
    $response = $robosats->createRobot($offer);
    return $response;
})->name('create-robot');


Route::post('/accept-offer', function () {
    $robosats = new Robosats();
    $offerId = request('offer_id');
    $offer = Offer::find($offerId);
    $response = $robosats->acceptOffer($offer->robosatsId);
    return $response;
})->name('accept-offer');

Route::post('/pay-bond', function () {
    $offerId = request('offer_id');
    $transaction = Transaction::where('offer_id', $offerId)->first();
    $invoice = $transaction->bond_invoice;
    $lightningNode = new LightningNode();
    $response = $lightningNode->payInvoice($invoice);
    return $response;
})->name('pay-bond');

Route::post('/pay-escrow', function () {
    // grab offer_id and transaction_id
    $offerId = request('offer_id');
    $transaction = Transaction::where('offer_id', $offerId)->first();
    $escrowInvoice = $transaction->escrow_invoice;
    // dd($escrowInvoice);
    $lightningNode = new LightningNode();
    $response = $lightningNode->payInvoice($escrowInvoice);
    return $response;
})->name('pay-escrow');


Route::post('/confirm-payment', function () {
    $offerId = request('offer_id');
    $offer = Offer::find($offerId);
    $transaction = Transaction::where('offer_id', $offerId)->first();
    $robosats = new Robosats();
    $response = $robosats->confirmReceipt($offer, $transaction);
    return $response;
})->name('confirm-payment');

Route::get('/claim-rewards', function () {
    $robosats = new Robosats();
    $robots = Robot::where('earned_rewards', '>', 0)->get();
    foreach ($robots as $robot) {
        $response = $robosats->claimCompensation($robot);
    }
    return response()->json(
        ['message' => 'Rewards claimed for' . count($robots) . ' robots',
        'robots' => $robots]
    );
})->name('claim-rewards');



// send-payment-handle
Route::post('/send-payment-handle', function () {
    $offerId = request('offer_id');
    $offer = Offer::find($offerId);
    $robosats = new Robosats();
    $response = $robosats->webSocketCommunicate($offer);
})->name('send-payment-handle');

//
Route::get('/testing', function () {

    $adminDashboard = AdminDashboard::all()->first();
    $maxConcurrentTransactions = $adminDashboard->max_concurrent_transactions;
    $transactions = Transaction::where('status', '<=', 11)->get();
    $transactionsCount = $transactions->count();
    if ($transactionsCount > $maxConcurrentTransactions) {
        return response()->json(['message' => 'Max concurrent transactions reached']);
    }
    // calculate difference
    $difference = $maxConcurrentTransactions - $transactionsCount;
    $offers = (new \App\Http\Controllers\OfferController)->getOffersInternal($adminDashboard);
    $paymentMethods = json_decode($adminDashboard->payment_methods);

    foreach ($offers as $offer) {
        // check if any of the payment methods are in the admin dashboard payment methods, if not remove the offer
        $found = false;
        if ($adminDashboard == null) {
            $paymentMethods = [];
        }
        foreach (json_decode($offer->payment_methods) as $paymentMethod) {
            if (in_array($paymentMethod, $paymentMethods)) {
                $found = true;
            }
        }
        if (!$found) {
            $offers = $offers->filter(function ($value, $key) use ($offer) {
                return $value->id != $offer->id;
            });
        }

        // check if the currency is in the admin dashboard currency, if not remove the offer
        if (!in_array($offer->currency, json_decode($adminDashboard->payment_currencies))) {
            $offers = $offers->filter(function ($value, $key) use ($offer) {
                return $value->id != $offer->id;
            });
        }


        // grab admin dashboard
        $adminDashboard = AdminDashboard::all()->first();
        $channelBalances = json_decode($adminDashboard->channelBalances, true);

        // grab the largest amount we can accept whether it is range or not
        $calculations = (new OfferController())->calculateLargestAmount($offer, $channelBalances);
        if (is_array($calculations)) {
            $offer->estimated_profit_sats = $calculations['estimated_profit_sats'];
        } else {
            return $calculations;
        }
    }




    // First, collect the premiums and estimated profits to calculate min and max
    $premiums = $offers->pluck('premium')->toArray();
    $profits = $offers->pluck('estimated_profit_sats')->toArray();

    $minPremium = min($premiums);
    $maxPremium = max($premiums);
    $minProfit = min($profits);
    $maxProfit = max($profits);

    // Score the offers using premium and estimated profit
    foreach ($offers as $offer) {
        $estimatedProfit = $offer->estimated_profit_sats;
        $premium = $offer->premium;

        // Normalize the premium and estimated profit
        $normalizedPremium = ($maxPremium - $minPremium) == 0 ? 0 : ($premium - $minPremium) / ($maxPremium - $minPremium);
        $normalizedProfit = ($maxProfit - $minProfit) == 0 ? 0 : ($estimatedProfit - $minProfit) / ($maxProfit - $minProfit);

        // Calculate the score
        $offer->score = 0.5 * $normalizedPremium + 0.5 * $normalizedProfit;
    }

    // Sort the offers by score
    $offers = $offers->sortByDesc('score');

    // Optional: If you want to reindex the array to ensure continuous numeric indexing
    $offers = $offers->values();

    // grab the first $difference offers
    $offers = $offers->take($difference);

    // chain 2 jobs, one to create the robots and one to accept the offers
    foreach ($offers as $offer) {
        $adminDashboard = AdminDashboard::all()->first();
        // Bus::chain([
        //     \App\Jobs\CreateRobots::dispatch($offer, $adminDashboard),
        //     \App\Jobs\AcceptSellOffer::dispatch($offer, $adminDashboard)
        // ])->dispatch();

        Bus::chain([
            \App\Jobs\CreateRobots::dispatch($offer, $adminDashboard),
            \App\Jobs\AcceptSellOffer::dispatch($offer, $adminDashboard)
        ])->dispatch();

    }

});



require __DIR__.'/auth.php';
