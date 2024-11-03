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
use App\Models\SlackMessage;
use App\Models\Transaction;
use App\Services\SlackService;
use App\Services\MonzoService;
use App\Services\PgpService;
use App\Services\RevolutService;
use App\Services\WiseService;
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
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use WebSocket\Connection;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware('auth')->group(function () {
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
    Route::get('/offer/{offer_id}', [\App\Http\Controllers\OfferController::class, 'getOffer'])->name('offers.show');
    Route::get('/completed-offers', [\App\Http\Controllers\OfferController::class, 'completedOffers'])->name('offers.completed');
    // Route::get('/offer/{offer_id}/chat', [\App\Http\Controllers\OfferController::class, 'chatRoom'])->name('offers.chat');
    // Route::post('/offer/{offer_id}/chat', [\App\Http\Controllers\OfferController::class, 'sendMessage'])->name('offers.chat');
    Route::post('/create-robot', [OfferController::class, 'createRobot'])->name('create-robot');
    Route::post('/accept-offer', [OfferController::class, 'acceptOffer'])->name('accept-offer');
    Route::post('/pay-bond', [OfferController::class, 'payBond'])->name('pay-bond');
    Route::post('/update-invoice', [OfferController::class, 'updateInvoice'])->name('update-invoice');
    Route::post('/pay-escrow', [OfferController::class, 'payEscrow'])->name('pay-escrow');
    Route::post('/confirm-payment', [OfferController::class, 'confirmPayment'])->name('confirm-payment');
    Route::get('/claim-rewards', [OfferController::class, 'claimRewards'])->name('claim-rewards');
    Route::post('/send-payment-handle', [OfferController::class, 'sendPaymentHandle'])->name('send-payment-handle');
    Route::post('/send-message', [OfferController::class, 'sendMessage'])->name('send-message');
    Route::post('auto-accept', [OfferController::class, 'autoAccept'])->name('auto-accept');
    Route::post('collaborative-cancel', [OfferController::class, 'collaborativeCancel'])->name('collaborative-cancel');


    Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/purchases', [\App\Http\Controllers\BtcPurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/config', [\App\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard.index');
    // add payment method
    Route::post('/add-payment-method', [\App\Http\Controllers\AdminDashboardController::class, 'addPaymentMethod'])->name('add-payment-method');
    Route::post('/update-payment-method/{id}', [\App\Http\Controllers\AdminDashboardController::class, 'updatePaymentMethod'])->name('update-payment-method');
    Route::get('/delete-payment-method/{id}', [\App\Http\Controllers\AdminDashboardController::class, 'deletePaymentMethod'])->name('delete-payment-method');

    Route::get('/graphs', [\App\Http\Controllers\GraphController::class, 'index'])->name('graphs.index');
    Route::get('/posting-offers', [\App\Http\Controllers\OfferTemplatesController::class, 'postingPage'])->name('offers.posting.index');
    Route::post('/create-template', [\App\Http\Controllers\OfferTemplatesController::class, 'createTemplate'])->name('create-template');
    Route::post('/edit-template', [\App\Http\Controllers\OfferTemplatesController::class, 'editTemplate'])->name('edit-template');
    Route::get('/delete-template/{id}', [\App\Http\Controllers\OfferTemplatesController::class, 'deleteTemplate'])->name('delete-template');

    Route::get('/simple', function () {
        return Inertia::render('Simple');
    })->name('simple');


    Route::get('/logs', function () {
        // Get worker logs //
        $logPath = storage_path('logs/worker.log');
        $log = file_get_contents($logPath);
        $logSegments = explode("\n", $log);
        $workerLogs = array_filter($logSegments, 'strlen');

        // Get laravel logs //
        $logPath = storage_path('logs/laravel.log');
        $log = file_get_contents($logPath);
        $logSegments = explode("\n", $log);
        $laravelLogs = array_filter($logSegments, 'strlen');

        // reverse the logs so the newest is at the top //
        $workerLogs = array_reverse($workerLogs);
        $laravelLogs = array_reverse($laravelLogs);

        // trim to 1000 lines
        $workerLogs = array_slice($workerLogs, 0, 1000);
        $laravelLogs = array_slice($laravelLogs, 0, 1000);
        return [
            'workerLogs' => $workerLogs,
            'laravelLogs' => $laravelLogs,
        ];
    })->name('logs');

    // create robot
    Route::get('create-robots', function () {
        $robot = new Robosats();
        return $robot->createRobots();
    });

    // route for displaying error messages
    Route::get('errors', function () {
        return Inertia::render('Errors');
    })->name('errors');

});

// route to get code from gmail for suave container
// Route::get('/get-gmail-code/', function () {
//     // grab start
//     if (isset(request()->start)) {
//         $start = request()->start;
//     } else {
//         $start = 'https://www.kraken.com/new-device-sign-in/web?code=';
//     }
//     $gmailService = new \App\Services\GmailService();
//     return $gmailService->getLinkFromLastEmail($start);
// });
//
//
// Route::get('/test-revolut-login', function () {
//     $url = 'http://'  . env('SUAVE_HOST', 'suave-py') .':' .  env('SUAVE_PORT', 8000) . '/revolut-login?' . http_build_query(['auto_bal_flag' => true]);
//     Http::post($url);
// });
//
// Route::get('grab-transactions', function () {
//     $mitmService = new \App\Services\MitmService();
//     $transactions = $mitmService->grabTransactions();
//     return $transactions;
// });



//
// Route::get('pgp-test', function () {
//     $pgpService = new PgpService();
//     $helper = new \App\WorkerClasses\HelperFunctions();
//     $highEntropyToken = $helper->generateSlug(16);
//     $keypair = $pgpService->generate_keypair($highEntropyToken);
//     // return $keypair;
//
//     $message = "Hello World";
//     $encrypted = $pgpService->encryptAndSign($keypair['public_key'], $keypair['public_key'],$message, $highEntropyToken);
//     $decrypted = $pgpService->decrypt($keypair['private_key'], $encrypted, $highEntropyToken);
//     $signed = $pgpService->sign($keypair['private_key'],$message, $highEntropyToken, $keypair['public_key']);
//     $verified = $pgpService->verify($keypair['public_key'],$signed);
//
//     return [
//         'message' => $message,
//         'encrypted' => $encrypted,
//         'decrypted' => $decrypted,
//         'signed' => $signed,
//         'verified' => $verified
//     ];
//
// });
//
// Route::get('test-kraken', function () {
//     $kraken = new \App\Services\KrakenService();
//     // kraken get BTC balance
//     $btcBalance = $kraken->getBTCBalance();
//     // return $btcBalance;
//     // if BTC balance greater than 0 send to lightning node
//     if ($btcBalance->isGreaterThan(BigDecimal::of('0.02'))) {
//         return "greater than 0.01";
//     }
//     return "less than 0.01";
// });
//
//
//
// Route::get('/wise-alternative', function() {
//     $wiseService = new \App\Services\WiseService();
//     $balanaces = $wiseService->getBalances();
//     // dd($balanaces);
//     $balStatement = $wiseService->getBalanceStatement('93380830');
//
//     dd($balStatement);
// });



// Route::get('/testing', function () {
//
//     $robosats = new Robosats();
//     $offer = Offer::where('robosatsId', '18227')->first();
//     $response = $robosats->updateInvoice($offer);
//     dd($response);
//
//     $slackService = new SlackService();
//     $channelId = 'C07L754M6TY';
//     for ($i = 0; $i < 1; $i++) {
//         $slackService->sendMessage('Test message ' . $i, $channelId);
//     }
//     $messages = $slackService->getLatestMessages($channelId);
//     dd($messages);
//
//     dd('done');
//
//     $mitmService = new \App\Services\MitmService();
//     $transactions = $mitmService->grabTransactions();
//
//     // iterate through the transactions and create a payment object for each
//     foreach ($transactions as $transaction) {
//         if ($transaction['state'] !== 'COMPLETED'
//             || Carbon::createFromTimestamp($transaction['completedDate'])->lt(Carbon::now()->subHour(1))
//             || $transaction['amount'] < 0) {
//             continue;
//         }
//         // check if transfer / topup
//         if (!in_array($transaction['type'], ['TRANSFER', 'TOPUP'])) {
//             continue;
//         }
//
//         $payment = new \App\Models\Payment();
//         $payment->payment_method = 'Revolut';
//         $payment->platform_transaction_id = $transaction['id'];
//         $payment->payment_reference = $transaction['comment'];
//
//         if (Payment::where('platform_transaction_id', $payment->platform_transaction_id)->exists()) {
//             continue;
//         }
//
//         $payment->payment_currency = $transaction['currency'];
//         $payment->payment_amount = $transaction['amount'] / 100;
//         $payment->platform_account_id = $transaction['account']['id'];
//         $payment->platform_description = $transaction['description'];
//         $payment->platform_entity = json_encode($transaction);
//
//
//         $payment->save();
//
//         $slackService = new SlackService();
//         $slackService->sendMessage('Payment received: ' . $payment->payment_amount . ' ' . $payment->payment_currency . ' on Revolut');
//
//
//         return response()->json(['message' => 'Payments created']);
//     }
//
// //      //!:TODO we need to figure out how to set the accepted amount and other shit inorder for auto accept to work
//     //     $robosats = new Robosats();
//     //     $providers = ['satstralia','lake']; //veneto  'temple',
//     //     $response = $robosats->createSellOffer(
//     //         "EUR",
//     //         20,
//     //         $providers[array_rand($providers)],
//     //         false,
//     //         20,
//     //         "Revolut",
//     //         2,
//     //         null
//     //     );
//     //
//     //     dd($response);
//     //
//     //
//     //
//     //     dd('testing');
//     //     $payment = null;
//     //     $wiseService = new \App\Services\WiseService();
//     //
//     //     // $gbpAccount = $wiseService->getGBPAccount();
//     //     // dd($gbpAccount);
//     //
//     //     // grab accounts
//     //     $accounts = $wiseService->getBalances();
//     //     $gbpAccount = null;
//     //     foreach ($accounts as $account) {
//     //         if ($account['currency'] == 'GBP') {
//     //             $gbpAccount = $account;
//     //         }
//     //     }
//     //
//     //     //wise delete all transfers
//     //     $transfers = $wiseService->getClient()->transfers->list(['offset' => 0, 'limit' => 100]);
//     //
//     //     foreach ($transfers as $transfer) {
//     //         if ($transfer['reference'] == "Send to Revolut" && $transfer['status'] != "cancelled") {
//     //             $wiseService->getClient()->transfers->cancel($transfer['id']);
//     //         }
//     //     }
//     //     // dd($transfers);
//     //
//     //
//     //     $recipients = $wiseService->getRecipientAccounts("GBP");
//     //
//     //     foreach ($recipients['content'] as $account) {
//     //         if ($account['id'] == env('WISE_RECIPIENT_ID_FOR_REVOLUT')) {
//     //
//     //             // $quote = $wiseService->createQuote("GBP", $wiseService->getGBPBalance(), $gbpAccount['id'], "GBP", $account['id'], "MOVING_MONEY_BETWEEN_OWN_ACCOUNTS");
//     //             $quote = $wiseService->createQuote("GBP", 4, $gbpAccount['id'], "GBP", $account['id'], "", "BANK_TRANSFER");
//     //             $transfer = $wiseService->transferToRecipient($quote['id'], $account['id'], "Send to Revolut");
//     //             $fundTransfer = $wiseService->fundTransfer($transfer['id']);
//     //             dd($fundTransfer);
//     //         }
//     //     }
//
// })->name('testing');
//


require __DIR__.'/auth.php';
