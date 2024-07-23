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

    $seleniumService->clickButtonsWithText($buttons, $buttonValues, ["Add withdrawal request"]);

    sleep(2);

    // find an input with id label and send keys to it
    $driver->findElement(WebDriverBy::id("label"))->click();
    $driver->findElement(WebDriverBy::id("label"))->sendKeys("ag.lightning invoice" . Carbon::now()->toDateTimeString());
    $driver->findElement(WebDriverBy::id("address"))->click();
    $driver->findElement(WebDriverBy::id("address"))->sendKeys($invoice);

    sleep(2);

    list($buttons, $buttonValues) = $seleniumService->getButtons();

    $seleniumService->clickButtonsWithText($buttons, $buttonValues, ["Add withdrawal request"]);

    // screenshot
    sleep(5);
    $driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
    $source = $driver->getPageSource();
    $driver->quit();
    dd($source, $seleniumService->linkUsed);


    try {
        $this->driver->wait(10, 1000)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id("label"))
        );
        // find an input with id label and send keys to it
        $driver->findElement(WebDriverBy::id("label"))->click();
        $driver->findElement(WebDriverBy::id("label"))->sendKeys("ag.lightning invoice" . Carbon::now()->toDateTimeString());
        $driver->findElement(WebDriverBy::id("address"))->click();
        $driver->findElement(WebDriverBy::id("address"))->sendKeys($invoice);

        // find a button with text "Add withdrawal address" and click it
        $text = "Add withdrawal address";
        $span = $driver->findElement(WebDriverBy::xpath("//*[contains(text(), '$text')]"))->click();
        // iterate up the dom tree until you find a button
        $button = null;
        $parent = $span;
        while ($button === null) {
            $parent = $parent->findElement(WebDriverBy::xpath(".."));
            if ($parent->getTagName() === "button") {
                $button = $parent;
            }
        }
        $button->click();
    } catch (\Exception $e) {
        $driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
        $source = $driver->getPageSource();
        $driver->quit();
        dd($source, $seleniumService->linkUsed);
    }


    // screenshot
    sleep(5);
    $driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
    $source = $driver->getPageSource();
    $driver->quit();
    dd($source, $seleniumService->linkUsed, $span, $button);


    $driver->wait(10, 1000)->until(
        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector(".ms-ds-0:nth-child(4) .text-ds-kraken-14-regular:nth-child(1) .text-ds-neutral:nth-child(2)"))
    );
    $driver->findElement(WebDriverBy::cssSelector(".ms-ds-0:nth-child(4) .text-ds-kraken-14-regular:nth-child(1) .text-ds-neutral:nth-child(2)"))->click();

    $driver->wait(10, 1000)->until(
        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector(".me-ds-0 .my-px"))
    );
    $element = $driver->findElement(WebDriverBy::cssSelector(".me-ds-0 .my-px"));
    $builder = new WebDriverActions($driver);
    $builder->moveToElement($element)->perform();

    $bodyElement = $driver->findElement(WebDriverBy::tagName("body"));
    $builder->moveToElement($bodyElement, 0, 0)->perform();
    $driver->executeScript("window.scrollTo(0,700.1058349609375)");
    $driver->findElement(WebDriverBy::cssSelector("#instant-btn-withdraw > .ms-ds-0"))->click();
    $driver->executeScript("window.scrollTo(0,0)");
    $driver->findElement(WebDriverBy::cssSelector(".db"))->click();

    $driver->wait(10, 1000)->until(
        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector("#downshift-1-item-1 > .flex-grow-1"))
    );
    $driver->findElement(WebDriverBy::cssSelector("#downshift-1-item-1 > .flex-grow-1"))->click();

    $element = $driver->findElement(WebDriverBy::cssSelector(".TextButton_root__fIpnJ > .text-ds-brand"));
    $builder->moveToElement($element)->perform();
    $driver->findElement(WebDriverBy::cssSelector(".TextButton_root__fIpnJ > .text-ds-brand"))->click();

    $bodyElement = $driver->findElement(WebDriverBy::tagName("body"));
    $builder->moveToElement($bodyElement, 0, 0)->perform();

    $driver->findElement(WebDriverBy::id("label"))->click();
    $driver->findElement(WebDriverBy::id("label"))->sendKeys("lightning invoice" . Carbon::now()->toDateTimeString());
    $driver->findElement(WebDriverBy::id("address"))->click();
    // $driver->findElement(WebDriverBy::id("address"))->sendKeys("lnbc20u1pnfu62xpp59xe5hwt7ynxng8sc9nufx27ua4rld7j0cvfar4umw29r6aa9v40sdqqcqzzsxqrrsssp52d4ek6ulujutfdhves80zlszaaenyyc9mjsfhf4rjn54ulsn2evs9qxpqysgqq3jfjysl60mxnp4065khaqph8r962v2ahccy6tfnqugxeggkq06nnqzjfzsmra93ecxlkjwvnxk3vufcncwh884zuu6tgz74zx4j22sqmrqkzc");
    $driver->findElement(WebDriverBy::id("address"))->sendKeys($invoice);




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
