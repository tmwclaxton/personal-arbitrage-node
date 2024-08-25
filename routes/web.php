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
use Spatie\DiscordAlerts\Facades\DiscordAlert;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use WebSocket\Connection;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

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
    Route::get('/completed-offers', [\App\Http\Controllers\OfferController::class, 'completedOffers'])->name('offers.completed');
    // Route::get('/offer/{offer_id}/chat', [\App\Http\Controllers\OfferController::class, 'chatRoom'])->name('offers.chat');
    // Route::post('/offer/{offer_id}/chat', [\App\Http\Controllers\OfferController::class, 'sendMessage'])->name('offers.chat');
    Route::post('/create-robot', [OfferController::class, 'createRobot'])->name('create-robot');
    Route::post('/accept-offer', [OfferController::class, 'acceptOffer'])->name('accept-offer');
    Route::post('/pay-bond', [OfferController::class, 'payBond'])->name('pay-bond');
    Route::post('/pay-escrow', [OfferController::class, 'payEscrow'])->name('pay-escrow');
    Route::post('/confirm-payment', [OfferController::class, 'confirmPayment'])->name('confirm-payment');
    Route::get('/claim-rewards', [OfferController::class, 'claimRewards'])->name('claim-rewards');
    Route::post('/send-payment-handle', [OfferController::class, 'sendPaymentHandle'])->name('send-payment-handle');
    Route::post('auto-accept', [OfferController::class, 'autoAccept'])->name('auto-accept');
    Route::post('collaborative-cancel', [OfferController::class, 'collaborativeCancel'])->name('collaborative-cancel');


    Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/purchases', [\App\Http\Controllers\BtcPurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/config', [\App\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/graphs', [\App\Http\Controllers\GraphController::class, 'index'])->name('graphs.index');
    Route::get('/posting-offers', [\App\Http\Controllers\OfferTemplatesController::class, 'postingPage'])->name('offers.posting.index');
    Route::post('/create-template', [\App\Http\Controllers\OfferTemplatesController::class, 'createTemplate'])->name('create-template');
    Route::post('/edit-template', [\App\Http\Controllers\OfferTemplatesController::class, 'editTemplate'])->name('edit-template');
    Route::get('/delete-template/{id}', [\App\Http\Controllers\OfferTemplatesController::class, 'deleteTemplate'])->name('delete-template');

});



Route::get('/test-revolut-login', function () {
    $url = 'http://'  . env('SUAVE_HOST', 'suave-py') .':' .  env('SUAVE_PORT', 8000) . '/revolut-login?' . http_build_query(['auto_bal_flag' => true]);
    Http::post($url);
});

Route::get('test-revolut-payment-listener', function () {
    // trigger job
    $job = new \App\Jobs\RevolutPaymentListener();
    $job->handle();
});

Route::get('grab-transactions', function () {
    $mitmService = new \App\Services\MitmService();
    $transactions = $mitmService->grabTransactions();
    return $transactions;
});

// Route::get('pgp-test', function () {
//     $pgpService = new PgpService();
//
// });

Route::get('test-kraken', function () {
    $kraken = new \App\Services\KrakenService();
    $kraken->sendFullAmtToLightning();
});

// Route::get('autoCreate', function () {
//     # grab all templates
//     $templates = \App\Models\PostedOfferTemplate::all();
//     foreach ($templates as $template) {
//         # check if the template is active
//         if ($template->auto_create) {
//             # check if the template quantity is less than matching offers
//             $count = Offer::where([['status', '<=', 1], ['posted_offer_template_id', $template->id]])->get()->count();
//             if ($template->quantity > $count) {
//
//                 for ($i = 0; $i < $template->quantity - $count; $i++) {
//                     $robosats = new \App\WorkerClasses\Robosats();
//                     $response = $robosats->createSellOffer(
//                         $template->currency,
//                         $template->premium,
//                         $template->provider,
//                         $template->min_amount,
//                         json_decode($template->payment_methods)[0],
//                         $template->bond_size,
//                         $template->id,
//                         $template->max_amount == 0 ? null : $template->max_amount,
//                     );
//
//                 }
//
//             }
//         }
//
//     }
// });

Route::get('/wise-alternative', function() {
    $wiseService = new \App\Services\WiseService();
    $balanaces = $wiseService->getBalances();
    // dd($balanaces);
    $balStatement = $wiseService->getBalanceStatement('93380830');

    dd($balStatement);
});



Route::get('/testing', function () {

    //!:TODO we need to figure out how to set the accepted amount and other shit inorder for auto accept to work
    $robosats = new Robosats();
    $providers = ['satstralia','lake']; //veneto  'temple',
    $response = $robosats->createSellOffer(
        "EUR",
        20,
        $providers[array_rand($providers)],
        20,
        "Revolut",
        2,
        null
    );

    dd($response);

    $mitmService = new \App\Services\MitmService();
    $transactions = $mitmService->grabTransactions();

    // iterate through the transactions and create a payment object for each
    foreach ($transactions as $transaction) {
        if ($transaction['state'] !== 'COMPLETED'
            || Carbon::createFromTimestamp($transaction['completedDate'])->lt(Carbon::now()->subHour(1))
            || $transaction['amount'] < 0) {
            continue;
        }
        // check if transfer / topup
        if (!in_array($transaction['type'], ['TRANSFER', 'TOPUP'])) {
            continue;
        }

        $payment = new \App\Models\Payment();
        $payment->payment_method = 'Revolut';
        $payment->platform_transaction_id = $transaction['id'];
        $payment->payment_reference = $transaction['comment'];

        if (Payment::where('platform_transaction_id', $payment->platform_transaction_id)->exists()) {
            continue;
        }

        $payment->payment_currency = $transaction['currency'];
        $payment->payment_amount = $transaction['amount'] / 100;
        $payment->platform_account_id = $transaction['account']['id'];
        $payment->platform_description = $transaction['description'];
        $payment->platform_entity = json_encode($transaction);


        $payment->save();

        $discordService = new DiscordService();
        $discordService->sendMessage('Payment received: ' . $payment->payment_amount . ' ' . $payment->payment_currency . ' on Revolut');


        return response()->json(['message' => 'Payments created']);
    }

//      //!:TODO we need to figure out how to set the accepted amount and other shit inorder for auto accept to work
    //     $robosats = new Robosats();
    //     $providers = ['satstralia','lake']; //veneto  'temple',
    //     $response = $robosats->createSellOffer(
    //         "EUR",
    //         20,
    //         $providers[array_rand($providers)],
    //         false,
    //         20,
    //         "Revolut",
    //         2,
    //         null
    //     );
    //
    //     dd($response);
    //
    //
    //
    //     dd('testing');
    //     $payment = null;
    //     $wiseService = new \App\Services\WiseService();
    //
    //     // $gbpAccount = $wiseService->getGBPAccount();
    //     // dd($gbpAccount);
    //
    //     // grab accounts
    //     $accounts = $wiseService->getBalances();
    //     $gbpAccount = null;
    //     foreach ($accounts as $account) {
    //         if ($account['currency'] == 'GBP') {
    //             $gbpAccount = $account;
    //         }
    //     }
    //
    //     //wise delete all transfers
    //     $transfers = $wiseService->getClient()->transfers->list(['offset' => 0, 'limit' => 100]);
    //
    //     foreach ($transfers as $transfer) {
    //         if ($transfer['reference'] == "Send to Revolut" && $transfer['status'] != "cancelled") {
    //             $wiseService->getClient()->transfers->cancel($transfer['id']);
    //         }
    //     }
    //     // dd($transfers);
    //
    //
    //     $recipients = $wiseService->getRecipientAccounts("GBP");
    //
    //     foreach ($recipients['content'] as $account) {
    //         if ($account['id'] == env('WISE_RECIPIENT_ID_FOR_REVOLUT')) {
    //
    //             // $quote = $wiseService->createQuote("GBP", $wiseService->getGBPBalance(), $gbpAccount['id'], "GBP", $account['id'], "MOVING_MONEY_BETWEEN_OWN_ACCOUNTS");
    //             $quote = $wiseService->createQuote("GBP", 4, $gbpAccount['id'], "GBP", $account['id'], "", "BANK_TRANSFER");
    //             $transfer = $wiseService->transferToRecipient($quote['id'], $account['id'], "Send to Revolut");
    //             $fundTransfer = $wiseService->fundTransfer($transfer['id']);
    //             dd($fundTransfer);
    //         }
    //     }

})->name('testing');



require __DIR__.'/auth.php';
