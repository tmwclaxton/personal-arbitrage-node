<?php

use App\Console\Commands\UpdateOffers;
use App\Http\Controllers\ProfileController;
use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\Robot;
use App\Models\Transaction;
use App\Services\PgpService;
use App\WorkerClasses\LightningNode;
use App\WorkerClasses\Robosats;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::post('/updateAdminDashboard', function () {
    $adminDashboard = AdminDashboard::all()->first();

    // iterate through the request and update the admin dashboard
    foreach (request()->adminDashboard as $key => $value) {
        // check if the key is in the admin dashboard
        // if not payment methods then update
        if (isset($adminDashboard->$key)) {
            if ($key != 'payment_methods') {
                $adminDashboard->$key = $value;
            } else {
                $adminDashboard->$key = json_encode($value);
            }
        }
    }


    $adminDashboard->save();
    // call command to update the offers refresh:robosat-offers
    // $updateOffers = new UpdateOffers();
    // $updateOffers->handle();
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

Route::post('/claim-rewards', function () {
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

    // update all current transactions
    $transactions = Transaction::whereNot('status', 'Sucessful trade')->get();
    foreach ($transactions as $transaction) {
        $offer = $transaction->offer;
        $robosats = new Robosats();
        $response = $robosats->updateTransactionStatus($offer);
    }
    return 'done';

    $robosats = new Robosats();
    $response = $robosats->webSocketCommunicate(Offer::find(80));
    return $response;

    $pgpService = new PgpService();
    $keypair = $pgpService->generate_keypair('-xsfdC?6QdY7NA+zwqw<q^e4S!MFKexQR*HXN');
    // remove new lines
    $private = str_replace("\n", '', $keypair['private_key']);
    $public = str_replace("\n", '', $keypair['public_key']);

    $sha256 = hash('sha256', '-xfdC?6QdY7NA+zwqw<q^e4S!MFKexQR*HXN');

    $b91 = new \Katoga\Allyourbase\Base91();
    $b91Token = $b91->encode(pack('H*', $sha256));
    $decoded = $b91->decode($b91Token);
    return([
        'private' => $private,
        'public' => $public,
        'b91Token' => $b91Token,
        'hex' => bin2hex($decoded)
    ]);
    $pgpService = new PgpService();
    $robot = Robot::find(1);
    $encrypt = $pgpService->encrypt($robot->private_key, 'hello');
    $decrypt = $pgpService->decrypt($robot->public_key, $encrypt, $robot->token);
    $sign = $pgpService->sign($robot->private_key, 'hello');
    $verify = $pgpService->verify($robot->public_key, $sign, 'hello');


    return [
        'encrypt' => $encrypt,
        'decrypt' => $decrypt,
        'sign' => $sign,
        'verify' => $verify
    ];

    // update all current transactions
    $offers = Offer::where('accepted', true)->where('expires_at', '<', now())->get();
    $robots = Robot::whereIn('offer_id', $offers->pluck('id'))->get();
    foreach ($robots as $robot) {
        $robosats = new Robosats();
        $response = $robosats->updateRobot($robot);
    }
    return 'done';

        // update all current transactions
    $transactions = Transaction::all();
    foreach ($transactions as $transaction) {
        $offer = $transaction->offer;
        $robosats = new Robosats();
        $response = $robosats->updateTransactionStatus($offer);
    }
    return 'done';




    //
    // $robosats = new Robosats();
    // $robosats->claimCompensation('', 'temple', '-----BEGIN PGP PRIVATE KEY BLOCK-----\n\nxYYEZnsonBYJKwYBBAHaRw8BAQdA50kwmUx1AunyYiukCXHcX8WKTcGbWhkC\nzmBV+anqoR7+CQMIDsjXel9rJMPg4OHL6eEQjTpKODKUb27/G5oEvcmsDxOn\nIaWg3kwZwpyLpDmXUVgWZEFqb6DLigqyCBc5K5I7NRroKr0ILZ8HQ3wHxZME\nXs1MUm9ib1NhdHMgSUQgOGY1ZTU4MmRjYzRiNjlkMjM2NjgxZjgzNGMxMGNj\nMmRiNWU0NjQwZmRiNzVhNDMzNTkwMWQ1NGIzMDA1MTRjM8KMBBAWCgA+BYJm\neyicBAsJBwgJkKypJt+M1B3PAxUICgQWAAIBAhkBApsDAh4BFiEE5ZaRtImc\nZ6D5bL2MrKkm34zUHc8AANkjAP99+0lYJYtLZJ5KsQVlOEE7MdDLdSuSOlpD\nE8y/HfgtkQEAqGWtPcTQBeVCadha47B5Qn7js2kbhpdAG62nqmadYA7HiwRm\neyicEgorBgEEAZdVAQUBAQdAX1L4Ldozcg1y6Pue5vvgFQR4lqGyZhpiGiEs\nA75M0F0DAQgH/gkDCOkwbVtNSDpW4FLyGxhtbMuhMOLyTTcf0bqVSGqLu5UU\njyDl0SUQYgRDACc2Gj49Pt7PO74f9MVBsbWcdewvd3P6KziHkAjCOvLK4o67\n4rvCeAQYFgoAKgWCZnsonAmQrKkm34zUHc8CmwwWIQTllpG0iZxnoPlsvYys\nqSbfjNQdzwAAb7oA/Rd4D3sXb6PKCPyplpb7gUmJ3SFOM6ui5PauEAQ36C7N\nAP9YYOBt9TdIsOZ5/VLc7kaXgLQZmqEKfRvaqMIiRT3UCw==\n=GLzY\n-----END PGP PRIVATE KEY BLOCK-----\n');



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
