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
use Brick\Math\BigDecimal;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use OTPHP\TOTP;
use PragmaRX\Google2FA\Google2FA;
use RevolutPHP\Auth\Provider;
use Spatie\DiscordAlerts\Facades\DiscordAlert;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;


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
    // $krakenService = new \App\Services\KrakenService();
    //
    // $kraken = new \App\Services\KrakenAPIService();
    // dd($krakenService->getClient()->getWithdrawalInformation('BTC', 'ag.lightning invoice2024-07-23 15:08:29', BigDecimal::of(0.0002)));
    //
    // $response = $kraken->krakenRequest('/0/private/Withdraw', [
    //     "asset" => "BTC",
    //     "key" => "ag.lightning invoice2024-07-23 15:08:29",
    //     "amount" => "0.00002",
    // ]);
    //
    // dd($response);



    $krakenService = new \App\Services\KrakenService();


    $btcBalance = $krakenService->getBTCBalance()->toFloat();
    $lightningNode = new LightningNode();
    $satoshis = 2000;
    $invoice = $lightningNode->createInvoice($satoshis, 'Kraken BTC Deposit of ' . $btcBalance . ' BTC at ' . Carbon::now()->toDateTimeString());


    $seleniumService = new \App\Services\SeleniumService();
    $driver = $seleniumService->getDriver();

    // sign in // possibly with otp
    $seleniumService->signin($krakenService);

    // approve device
    $seleniumService->approveDevice();

    // set session key lightning-network-shown-in-current-session to true
    $driver->executeScript("window.localStorage.setItem('lightning-network-shown-in-current-session', 'true')");

    // sign in again
    $seleniumService->signin($krakenService, 'https://www.kraken.com/c/funding/withdraw?asset=BTC&assetType=crypto&network=Lightning&method=Bitcoin%2520Lightning');

    sleep(6);

    list($buttons, $buttonValues) = $seleniumService->getButtons();

    $seleniumService->clickButtonsWithText($buttons, $buttonValues, ["Okay", "Agree and continue"]);

    // scroll down slightly
    $driver->executeScript("window.scrollTo(0,700.1058349609375)");

    sleep(2);


    list($buttons, $buttonValues) = $seleniumService->getButtons();

    $seleniumService->clickButtonsWithText($buttons, $buttonValues, ["Manage withdrawal requests", "Add withdrawal request"]);

    sleep(5);

    try {
        // find an input with id label and send keys to it
        $driver->findElement(WebDriverBy::id("label"))->click();
        $invoiceId = "ag_lightning_invoice_" . Carbon::now()->toDateTimeString();
        $driver->findElement(WebDriverBy::id("label"))->sendKeys($invoiceId);
        $driver->findElement(WebDriverBy::id("address"))->click();
        $driver->findElement(WebDriverBy::id("address"))->sendKeys($invoice);
    } catch (\Exception $e) {
        $driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
        $source = $driver->getPageSource();
        $driver->quit();
        dd($source, $e, $buttons, $buttonValues);
    }

    sleep(2);

    list($buttons, $buttonValues) = $seleniumService->getButtons();

    $seleniumService->clickButtonsWithText($buttons, $buttonValues, ["Add withdrawal request"]);

    sleep(2);

    // grab email
    $this->driver->get($seleniumService->getLinkFromLastEmail('https://www.kraken.com/withdrawal-approve?code='));

    // screenshot
    sleep(5);
    $driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
    $source = $driver->getPageSource();
    $driver->quit();
    dd($source, $seleniumService->linkUsed);




    // Close the driver
    $driver->quit();
    return response()->json(['invoice' => $invoice]);



    // we need to use php webdriver at this point


    // $bigDecimal = BigDecimal::of($btc);
    // $withdrawalMethods = $krakenService->getClient()->getWithdrawalInformation('XBT', 'currency', $bigDecimal);
    // dd($withdrawalMethods);
    // dd($invoiceDetails);


    // $kraken = new \App\Services\KrakenAPIService();
    //
    // $response = $kraken->krakenRequest('/0/private/Withdraw', [
    //     'asset' => 'XXBT',
    //     'key' => 'name of address',
    //     // 'address' => $invoice,
    //     'amount' => $btc,
    // ]);
    //
    // print_r($response);



})->name('testing');


require __DIR__.'/auth.php';
