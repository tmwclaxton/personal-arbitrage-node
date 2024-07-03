<?php

use App\Console\Commands\UpdateOffers;
use App\Http\Controllers\ProfileController;
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


Route::get('/testing', function () {
    $lastMessages = (new DiscordService())->getLastMessages();
    return $lastMessages;
    $response =  (new DiscordService())->detectCommand("/send");
    return $response;


    //
    // $robosats = new Robosats();
    // $robosats->claimCompensation('', 'temple', '-----BEGIN PGP PRIVATE KEY BLOCK-----\n\nxYYEZnsonBYJKwYBBAHaRw8BAQdA50kwmUx1AunyYiukCXHcX8WKTcGbWhkC\nzmBV+anqoR7+CQMIDsjXel9rJMPg4OHL6eEQjTpKODKUb27/G5oEvcmsDxOn\nIaWg3kwZwpyLpDmXUVgWZEFqb6DLigqyCBc5K5I7NRroKr0ILZ8HQ3wHxZME\nXs1MUm9ib1NhdHMgSUQgOGY1ZTU4MmRjYzRiNjlkMjM2NjgxZjgzNGMxMGNj\nMmRiNWU0NjQwZmRiNzVhNDMzNTkwMWQ1NGIzMDA1MTRjM8KMBBAWCgA+BYJm\neyicBAsJBwgJkKypJt+M1B3PAxUICgQWAAIBAhkBApsDAh4BFiEE5ZaRtImc\nZ6D5bL2MrKkm34zUHc8AANkjAP99+0lYJYtLZJ5KsQVlOEE7MdDLdSuSOlpD\nE8y/HfgtkQEAqGWtPcTQBeVCadha47B5Qn7js2kbhpdAG62nqmadYA7HiwRm\neyicEgorBgEEAZdVAQUBAQdAX1L4Ldozcg1y6Pue5vvgFQR4lqGyZhpiGiEs\nA75M0F0DAQgH/gkDCOkwbVtNSDpW4FLyGxhtbMuhMOLyTTcf0bqVSGqLu5UU\njyDl0SUQYgRDACc2Gj49Pt7PO74f9MVBsbWcdewvd3P6KziHkAjCOvLK4o67\n4rvCeAQYFgoAKgWCZnsonAmQrKkm34zUHc8CmwwWIQTllpG0iZxnoPlsvYys\nqSbfjNQdzwAAb7oA/Rd4D3sXb6PKCPyplpb7gUmJ3SFOM6ui5PauEAQ36C7N\nAP9YYOBt9TdIsOZ5/VLc7kaXgLQZmqEKfRvaqMIiRT3UCw==\n=GLzY\n-----END PGP PRIVATE KEY BLOCK-----\n');
});



require __DIR__.'/auth.php';
