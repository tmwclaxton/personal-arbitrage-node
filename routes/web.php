<?php

use App\Console\Commands\UpdateOffers;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProfileController;
use App\Jobs\ConfirmPayment;
use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\RevolutAccessToken;
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
    $authProvider = new \RevolutPHP\Auth\Provider([
        'clientId' => env('REVOLUT_CLIENT_ID'),
        'privateKey' => 'file://' . storage_path('app/private/RevolutCerts/privatecert.pem'),
        'redirectUri' => env('REVOLUT_REDIRECT_URI'),
        'isSandbox' => false,
    ]);

    // grab admin dashboard
    $adminDashboard = AdminDashboard::all()->first();
    // grab the revolut code
    $revolutCode = $adminDashboard->revolut_code;
    // if there are none, create a new one

    // if revolut code is null, then we need to create a new RevolutAccessToken
    if ($revolutCode == null && RevolutAccessToken::all()->count() == 0) {
        $url = $authProvider->getAuthorizationUrl();
        return redirect($url);
    }

    // check if there are any RevolutAccessToken
    if( RevolutAccessToken::all()->count() == 0 ) {

        $accessToken = $authProvider->getAccessToken('authorization_code', [
            'code' => $revolutCode
        ]);

        RevolutAccessToken::create([
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $accessToken->getRefreshToken(),
            'expires' => $accessToken->getExpires(),
        ]);

        // set the revolut code to null
        $adminDashboard->revolut_code = null;
        $adminDashboard->save();
    } else {
        // Grab the most recent RevolutAccessToken
        $revolutAccessToken = RevolutAccessToken::all()->last();
        // convert RevolutAccessToken to AccessToken
        $revolutAccessToken = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $revolutAccessToken->access_token,
            'refresh_token' => $revolutAccessToken->refresh_token,
            'expires' => $revolutAccessToken->expires,
        ]);

        // if the token is expired
        if( $revolutAccessToken->hasExpired() ) {

            $newAccessToken = $authProvider->getAccessToken('refresh_token', [
                'refresh_token' => $revolutAccessToken->getRefreshToken()
            ]);

            RevolutAccessToken::create([
                'access_token' => $newAccessToken->getToken(),
                'refresh_token' => $newAccessToken->getRefreshToken(),
                'expires' => $newAccessToken->getExpires(),
            ]);
        }
    }

    $accessToken = RevolutAccessToken::all()->last()->access_token;
    // convert RevolutAccessToken to AccessToken
    $accessToken = new \League\OAuth2\Client\Token\AccessToken([
        'access_token' => $accessToken,
    ]);

    $client = new \RevolutPHP\Client($accessToken);
    $accounts = $client->accounts->all();
    dd($accounts);

})->name('testing');


require __DIR__.'/auth.php';
