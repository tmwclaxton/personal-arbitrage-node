<?php

use App\Console\Commands\UpdateOffers;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProfileController;
use App\Jobs\ConfirmPayment;
use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\BtcPurchase;
use App\Models\MonzoAccessToken;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\RevolutAccessToken;
use App\Models\Robot;
use App\Models\Transaction;
use App\Services\DiscordService;
use App\Services\MonzoService;
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
use Illuminate\Http\Client\ConnectionException;
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
use WebSocket\Connection;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;


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
Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'index'])->name('transactions.index');
Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
Route::get('/config', [\App\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard.index');


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

Route::get('monzo-redirect', function () {
    $monzoService = new MonzoService();
    $redirect = $monzoService->redirectUserToMonzo();
    return response()->json(['redirect' => $redirect]);
})->name('monzoRedirect');

Route::get('monzo-exchange', function () {
    $monzoService = new MonzoService();
    $code = 'eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9.eyJlYiI6InhYL2pmTXpwUm54VFBZYzR3dzZKIiwianRpIjoiYXV0aHpjb2RlXzAwMDBBa0ZvZDBOakY3WndQdFY1aGgiLCJ0eXAiOiJhemMiLCJ2IjoiNiJ9.Cj7uispLSPHalEIYzjmC7IY-EsvBkEtPJBXfsed2o5pIz3IWOTqzLXwwckOHUwiCLmHxZUMCIrr6a_MZHYJFcw';
    $exchange = $monzoService->exchangeCode($code);
    return response()->json(['exchange' => $exchange]);
})->name('monzoExchange');

Route::get('monzo-refresh', function () {
    $monzoService = new MonzoService();
    $monzoAccessToken = MonzoAccessToken::all()->first();
    $refreshedToken = $monzoService->refreshAccessToken($monzoAccessToken);
    return response()->json(['refreshedToken' => $refreshedToken]);
})->name('monzoRefresh');


Route::get('/testing', function () {

    // $seleniumService = new \App\Services\SeleniumService();
    // dd($seleniumService->getLinkFromLastEmail());

    // $response = $krakenService->getClient()->getAccountBalance();
    // dd($response);


    $offer = Offer::find(340);

    $robot = $offer->robots()->first();

    $b91 = new \Katoga\Allyourbase\Base91();
    $decoded = $b91->decode($robot->sha256);
    $hex = bin2hex($decoded);
    $url = 'ws://192.168.0.18:12596' . '/mainnet/' . $offer->provider . '/ws/chat/' . $offer->robosatsId . '/?token_sha256_hex=' . $hex;

    // create a new client
    $client = new \WebSocket\Client($url);
    $messages = [];

    $client->text(json_encode([
        'type' => 'message',
        'message' => $robot->public_key,
        'nick' => $robot->nickname
    ]));

    $client->text(json_encode([
        'type' => 'message',
        'message' => '-----SERVE HISTORY-----',
        'nick' => $robot->nickname
    ]));

    $startTime = time();
    $duration = 10; // Duration in seconds

    try {
        while (true) {
            try {
                $message = $client->receive();
                if ($message) {
                    $messages[] = $message;
                }
            } catch (ConnectionException $e) {
                // Handle timeout or connection error
                sleep(1);
                break;
            }

            // Exit the loop after 15 seconds
            if (time() - $startTime > $duration) {
                break;
            }
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
    }

    // filter messages for opcode text
    $messages = array_filter($messages, function ($message) {
        return $message->getOpcode() == "text";
    });

    // each message is type WebSocket\Message\Text and we need to grab the content property first
    $messages = array_map(function ($message) {
        return $message->getContent();
    }, $messages);

    // filter messages if they have a key of 'index'
    $messages = array_filter($messages, function ($message) {
        $message = json_decode($message, true);
        return array_key_exists('index', $message);
    });

    // sort messages by index
    usort($messages, function ($a, $b) {
        $a = json_decode($a, true);
        $b = json_decode($b, true);
        return $a['index'] <=> $b['index'];
    });

    // $myMessages = [];
    // $theirMessages = [];
    // foreach ($messages as $message) {
    //     $message = json_decode($message, true);
    //     if ($message['user_nick'] == $robot->nickname) {
    //         $myMessages[] = $message;
    //     } else {
    //         $theirMessages[] = $message;
    //     }
    // }
    //
    // // decode my messages with my private key
    // $pgpService = new PgpService();
    // $myDecodedMessages = [];
    // foreach ($myMessages as $message) {
    //     $myDecodedMessages[] = $pgpService->decrypt($message['message'], $robot->private_key, $robot->token);
    // }

    // decrypt all messages with my private key
    $pgpService = new PgpService();
    $privateKey = $robot->private_key;

    $formattedMessages = [];
    foreach ($messages as $message) {
        $message = json_decode($message, true);
        $content = $message['message'];
        // user_nick and
        $content = str_replace("\\", "\n", $content);
        $decodedMessage = $pgpService->decrypt($privateKey, $content, $robot->token);
        $formattedMessages[] = [
            'index' => $message['index'],
            'user_nick' => $message['user_nick'],
            'time' => $message['time'],
            'message' => $decodedMessage
        ];
    }


    dd($messages, $formattedMessages);









    // $revolutService = new RevolutService();
    // $revolutService->currencyExchangeAll("EUR", "GBP");
    // dd($revolutService->getGBPBalance());
    // wise send to personal revolut account
    $payment = null;
    $wiseService = new \App\Services\WiseService();

    // $gbpAccount = $wiseService->getGBPAccount();
    // dd($gbpAccount);

    // grab accounts
    $accounts = $wiseService->getBalances();
    $gbpAccount = null;
    foreach ($accounts as $account) {
        if ($account['currency'] == 'GBP') {
            $gbpAccount = $account;
        }
    }

    //wise delete all transfers
    $transfers = $wiseService->getClient()->transfers->list(['offset' => 0, 'limit' => 100]);

    foreach ($transfers as $transfer) {
        if ($transfer['reference'] == "Send to Revolut" && $transfer['status'] != "cancelled") {
            $wiseService->getClient()->transfers->cancel($transfer['id']);
        }
    }
    // dd($transfers);


    $recipients = $wiseService->getRecipientAccounts("GBP");

    foreach ($recipients['content'] as $account) {
        if ($account['id'] == env('WISE_RECIPIENT_ID_FOR_REVOLUT')) {

            // $quote = $wiseService->createQuote("GBP", $wiseService->getGBPBalance(), $gbpAccount['id'], "GBP", $account['id'], "MOVING_MONEY_BETWEEN_OWN_ACCOUNTS");
            $quote = $wiseService->createQuote("GBP", 4, $gbpAccount['id'], "GBP", $account['id'], "", "BANK_TRANSFER");
            $transfer = $wiseService->transferToRecipient($quote['id'], $account['id'], "Send to Revolut");
            $fundTransfer = $wiseService->fundTransfer($transfer['id']);
            dd($fundTransfer);
        }
    }





})->name('testing');

// private function to convert bigDecimal to decimal(16, 8)


require __DIR__.'/auth.php';
