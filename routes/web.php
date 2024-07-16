<?php

use App\Console\Commands\UpdateOffers;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProfileController;
use App\Jobs\ConfirmPayment;
use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\RevolutAccessToken;
use App\Models\Robot;
use App\Models\Transaction;
use App\Services\DiscordService;
use App\Services\PgpService;
use App\Services\RevolutService;
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

    foreach (request()->adminDashboard as $key => $value) {
        // check if key does exist
        if (key_exists($key, $adminDashboard->getAttributes())) {
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


// auto-accept
Route::post('auto-accept', function () {
    $adminDashboard = AdminDashboard::all()->first();
    $offerId = request('offer_id');
    $offer = Offer::where('id', $offerId)->first();
    Bus::chain([
        new \App\Jobs\CreateRobots($offer, $adminDashboard),
        new \App\Jobs\AcceptSellOffer($offer, $adminDashboard)
    ])->dispatch();
})->name('auto-accept');


Route::get('/testing', function () {

    // set up wise client
    $client = new \TransferWise\Client(
        [
            "token" => env('WISE_API_KEY'),
            "profile_id" => "test",
            // "env" => "sandbox" // optional
        ]
    );

    $profiles = $client->profiles->all();
    // dd($profiles);

    $wiseService = new \App\Services\WiseService();
    $response = $wiseService->getActivities($profiles[0]['id']);
    $activities = $response['activities'];
    // dd($activities);
    foreach ($activities as $activity) {
        if (
            $activity['type'] === "TRANSFER" &&
            $activity['description'] !== "<strong>Toby Matthew William Claxton</strong>" &&
            $activity['status'] === "COMPLETED" &&
            str_contains($activity['primaryAmount'], '+') &&
            $activity['createdOn'] > Carbon::now()->subHour(1)
        ) {
            // $activity['primaryAmount'] = '<positive>+ 200 EUR</positive>' -> 200 EUR
            $activity['formattedAmount'] = trim(str_replace('+', '', str_replace(['<positive>', '</positive>'], '', $activity['primaryAmount'])));

            // if secondaryAmount is not null, then we need to overwrite the formattedAmount with the secondaryAmount
            if ($activity['secondaryAmount'] !== "") {
                $activity['formattedAmount'] = $activity['secondaryAmount'];
            }

            // now that we have a formattedAmount in the form x.x CURRENCY, we need to split it into amount and currency
            $activity['amount'] = explode(' ', $activity['formattedAmount'])[0];
            // if amount is 0, then we skip this activity
            if ($activity['amount'] == 0) {
                continue;
            }

            $activity['currency'] = explode(' ', $activity['formattedAmount'])[1];

            // add a column for sender  "title" => "<strong>Igor Pinto Borges</strong>"
            $activity['sender'] = trim(str_replace(['<strong>', '</strong>'], '', $activity['title']));

            $payment = new \App\Models\Payment();
            $payment->payment_method = 'Wise';
            $payment->platform_transaction_id = $activity['id'];

            if (Payment::where('platform_transaction_id', $payment->platform_transaction_id)->exists()) {
                continue;
            }

            $payment->payment_currency = $activity['currency'];
            $payment->payment_amount = $activity['amount'];
            $payment->platform_account_id = $activity['sender'];
            $payment->platform_description = $activity['description'];
            $payment->platform_entity = json_encode($activity);

            $payment->save();

            $discordService = new DiscordService();
            $discordService->sendMessage('Payment received: ' . $payment->payment_amount . ' ' . $payment->payment_currency . ' on Wise');



        }
    }

    dd($activities);


    // now grab the id



    $client = new \Butschster\Kraken\Client(
        new GuzzleHttp\Client(),
        new \Butschster\Kraken\NonceGenerator(),
        (new \Butschster\Kraken\Serializer\SerializerFactory())->build(),
        env('KRAKEN_API_KEY'),
        env('KRAKEN_PRIVATE_KEY')
    );

    // get info to make a deposit
    $response = $client->getAccountBalance();


})->name('testing');


require __DIR__.'/auth.php';
