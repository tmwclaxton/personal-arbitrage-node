<?php

namespace App\Services;

use App\Models\AdminDashboard;
use App\Models\RevolutAccessToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use RevolutPHP\Auth\Provider;

class RevolutService
{
    private Provider $authProvider;

    private AdminDashboard $adminDashboard;

    public function __construct()
    {
        // check if the private key, public key, and x509 certificate exists
        if ( !file_exists(storage_path('app/private/RevolutCerts/privatekey.pem')) ) {
            // create the directory if it does not exist
            if (!file_exists(storage_path('app/private/RevolutCerts'))) {
                mkdir(storage_path('app/private/RevolutCerts'), 0777, true);
            }


            // generate x509 certificate
            $pgpService = new PgpService();
            $response = $pgpService->generateX509Certificates();

            // save the private key, public key, and x509 certificate to app/storage/private
            $privateKey = $response['private_key'];
            $publicKey = $response['public_key'];
            $x509 = $response['x509'];

            // save the private key, public key, and x509 certificate to app/storage/private
            file_put_contents(storage_path('app/private/RevolutCerts/privatekey.pem'), $privateKey);
            file_put_contents(storage_path('app/private/RevolutCerts/publickey.pem'), $publicKey);
            file_put_contents(storage_path('app/private/RevolutCerts/x509.pem'), $x509);
        }

        $this->authProvider = new Provider([
            'clientId' => env('REVOLUT_CLIENT_ID'),
            'privateKey' => 'file://' . storage_path('app/private/RevolutCerts/privatekey.pem'),
            'redirectUri' => env('REVOLUT_REDIRECT_URI'),
            'isSandbox' => false,
        ]);

        // grab admin dashboard
        $this->adminDashboard = AdminDashboard::all()->first();

    }

    public function getToken($type) {
        // if revolut code is null, then we need to create a new RevolutAccessToken
        if ($this->adminDashboard->revolut_code == null && RevolutAccessToken::where('type', $type)->count() == 0) {

            // Define the Redis key for tracking the request time
            $redisKey = 'revolut_auth_code_request';

            // Check if the key exists in Redis
            if (Redis::get($redisKey)) {
                return ['message' => 'Please wait for 10 minutes before requesting another authorization code'];
            }

            // scope PAY
            $url = $this->authProvider->getAuthorizationUrl([
                'scope' => $type,
            ]);

            Redis::set($redisKey, true, 'EX', 600);


            return ['url' => $url, 'message' => 'Please visit the URL to get the code'];
        }

        // check if there are any RevolutAccessToken
        if( RevolutAccessToken::where('type', $type)->count() == 0 ) {

            $accessToken = $this->authProvider->getAccessToken('authorization_code', [
                'code' => $this->adminDashboard->revolut_code
            ]);

            $revolutAccessToken = RevolutAccessToken::create([
                'type' => $type,
                'access_token' => $accessToken->getToken(),
                'refresh_token' => $accessToken->getRefreshToken(),
                'expires' => $accessToken->getExpires(),
            ]);

            // set the revolut code to null
            $this->adminDashboard->revolut_code = null;
            $this->adminDashboard->save();
        } else {
            $revolutAccessToken = RevolutAccessToken::where('type', $type)->first();
            // convert RevolutAccessToken to AccessToken
            $accessToken = new \League\OAuth2\Client\Token\AccessToken([
                'access_token' => $revolutAccessToken->access_token,
                'refresh_token' => $revolutAccessToken->refresh_token,
                'expires' => $revolutAccessToken->expires,
            ]);
            // if the token is expired
            if ($accessToken->hasExpired()) {

                //
                try {
                    $newAccessToken = $this->authProvider->getAccessToken('refresh_token', [
                        'refresh_token' =>  $revolutAccessToken->refresh_token
                    ]);
                } catch (\Exception $e) {
                    // if the refresh token is invalid, then  delete the RevolutAccessToken
                    $revolutAccessToken->delete();
                    // error out
                    return "Refresh token is invalid";
                }

                $revolutAccessToken = RevolutAccessToken::where('refresh_token',  $revolutAccessToken->refresh_token)->first();
                $revolutAccessToken->access_token = $newAccessToken->getToken();
                $revolutAccessToken->expires = $newAccessToken->getExpires();
                $revolutAccessToken->save();

                // convert RevolutAccessToken to AccessToken
                $accessToken = new \League\OAuth2\Client\Token\AccessToken([
                    'access_token' => $revolutAccessToken->access_token,
                    'refresh_token' => $revolutAccessToken->refresh_token,
                    'expires' => $revolutAccessToken->expires,
                ]);

            }

        }

        // return $revolutAccessToken->access_token;
        return ['access_token' => $accessToken->getToken(), 'message' => 'Access token retrieved'];
    }

    public function getReadToken() {
        return $this->getToken('READ');
    }
    public function getPayToken() {
        return $this->getToken('PAY');
    }

    public function currencyExchangeAll($fromCurrency, $toCurrency, $reference = null, $requestId = null, $minAmount = 5) {
        // we need two types of access tokens READ and PAY

        $revArray = $this->getReadToken();
        if (array_key_exists('url', $revArray)) {
            $discordService = new DiscordService();
            $discordService->sendMessage('Reset RevToken at: ' . $revArray['url']);
            return;
        } else {
            $token = $revArray['access_token'];
        }

        // convert RevolutAccessToken to AccessToken
        $accessToken = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $token,
        ]);

        try {
            $client = new \RevolutPHP\Client($accessToken);
            $accounts = $client->accounts->all();
        } catch (\Exception $e) {
            $discordService = new DiscordService();
            $discordService->sendMessage('Revolut Currency Exchange Failed: ' . $e->getMessage());
            return;
        }

        // using the GBP account convert all to EUR
        // iterate through the accounts and grab the GBP and EUR accounts
        foreach ($accounts as $account) {
            // these must be above 0 as revolut has fake accounts or something
            if ($account->currency == $fromCurrency && $account->balance > $minAmount && $account->balance > 0) {
                $fromAccount = $account;
            }

            if ($toCurrency == "GBP") {
                // we have 2 GBP accounts
                if ($account->id == env('REVOLUT_GBP_ACCOUNT_ID')) {
                    $toAccount = $account;
                }
            } else {
                if ($account->currency == $toCurrency) {
                    $toAccount = $account;
                }
            }
        }
        if (!isset($fromAccount)) {
            return;
        }
        if (!isset($toAccount)) {
            $discordService = new DiscordService();
            $discordService->sendMessage('Revolut Currency Exchange Failed: ' . 'No account found for ' . $toCurrency);
            return;
        }

        $exchange = [
            'from' => [
                'account_id' => $fromAccount->id,
                'currency' => $fromCurrency,
                'amount' => $fromAccount->balance,
            ],
            'to' => [
                'account_id' => $toAccount->id,
                'currency' => 'GBP',
            ],
            'reference' => $reference ?? 'exchange - ' . time(),
            'request_id' => $requestId ?? hash('sha256', time() . 'exchange')
        ];

        $token = null;
        $revArray = $this->getPayToken();
        if (array_key_exists('url', $revArray)) {
            $discordService = new DiscordService();
            $discordService->sendMessage('Reset RevToken at: ' . $revArray['url']);
            return;
        } else {
            $token = $revArray['access_token'];
        }
        // convert RevolutAccessToken to AccessToken
        $accessToken = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $token,
        ]);

        try {
            $client = new \RevolutPHP\Client($accessToken);
            $response = $client->exchanges->exchange($exchange);
        } catch (\Exception $e) {
            $discordService = new DiscordService();
            $discordService->sendMessage('Revolut Currency Exchange Failed: ' . $e->getMessage());
            return;
        }

        // if state is completed then we are good
        if ($response->state == 'completed') {
            $discordService = new DiscordService();
            $discordService->sendMessage('Revolut Currency Exchange Completed of ' . $fromAccount->balance . ' ' . $fromCurrency . ' to ' . $toCurrency);
        } else {
            $discordService = new DiscordService();
            $discordService->sendMessage('Revolut Currency Exchange Failed: ' . json_encode($response));
        }
    }

    public function getTransactions()
    {
        $revArray = $this->getReadToken();
        if (array_key_exists('url', $revArray)) {
            $discordService = new DiscordService();
            $discordService->sendMessage('Reset RevToken at: ' . $revArray['url']);
            return [];
        } else {
            $token = $revArray['access_token'];
        }

        // convert RevolutAccessToken to AccessToken
        $accessToken = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $token,
        ]);

        // $client = new \RevolutPHP\Client($accessToken);
        // $transactions $client->transactions->all();
        try {
            $client = new \RevolutPHP\Client($accessToken);
            $transactions = $client->transactions->all();
        } catch (\Exception $e) {
            $discordService = new DiscordService();
            $discordService->sendMessage('Revolut Transactions Failed: ' . $e->getMessage());
            return [];
        }

        return $transactions;
    }


    // send gbp to an account using manual transfer
    public function sendGBP($amount, $accountNumber, $sortCode, $reference = null, $requestId = null)
    {
        $revArray = $this->getPayToken();
        if (array_key_exists('url', $revArray)) {
            $discordService = new DiscordService();
            $discordService->sendMessage('Reset RevToken at: ' . $revArray['url']);
            return;
        } else {
            $token = $revArray['access_token'];
        }

        // convert RevolutAccessToken to AccessToken
        $accessToken = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $token,
        ]);

        $client = new \RevolutPHP\Client($accessToken);

        $response = $client->transfers->create([
            'account_id' => $accountNumber,
            'amount' => $amount,
            'currency' => 'GBP',
            'reference' => $reference ?? time() . 'transfer',
            'request_id' => $requestId ?? hash('sha256', time() . 'transfer'),
            'target_account_id' => $accountNumber,
            'target_currency' => 'GBP',
            'target_account_type' => 'gb',
            'target_account_sort_code' => $sortCode,
        ]);

        return $response;
    }

    public function getGBPBalance()
    {
        $revArray = $this->getReadToken();
        if (array_key_exists('url', $revArray)) {
            $discordService = new DiscordService();
            $discordService->sendMessage('Reset RevToken at: ' . $revArray['url']);
            return [];
        } else {
            $token = $revArray['access_token'];
        }

        // convert RevolutAccessToken to AccessToken
        $accessToken = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $token,
        ]);

        $client = new \RevolutPHP\Client($accessToken);
        $accounts = $client->accounts->all();

        // using the GBP account convert all to EUR
        // iterate through the accounts and grab the GBP and EUR accounts
        foreach ($accounts as $account) {
            // these must be above 0 as revolut has fake accounts or something
            if ($account->currency == 'GBP' && $account->balance > 0) {
                return $account->balance;
            }
        }
        return 0;
    }

    public function sendAllGBPToAccount($accountId) {
        // revolut send to personal account
        $payment = null;
        $accessToken = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $this->getReadToken()['access_token']
        ]);

        if ($this->getGBPBalance() >= 20) {
            $discordService = new DiscordService();
            $discordService->sendMessage('Sending ' . $this->getGBPBalance() . ' GBP to Kraken account');
            $client = new \RevolutPHP\Client($accessToken);
            $counterParties = $client->counterparties->all();
            $counterParty = null;
            foreach ($counterParties as $cp) {
                if ($cp->id === $accountId) {
                    $counterParty = $cp;
                    break;
                }
            }
            if ($counterParty == null) {
                $discordService->sendMessage('Counterparty not found to send GBP to');
                return;
            }
            $payment = array(
                "request_id" => bin2hex(random_bytes(16)),
                "account_id" => env('REVOLUT_GBP_ACCOUNT_ID'),
                "receiver" => array(
                    "counterparty_id" => $counterParty->id,
                    "account_id" => $counterParty->accounts[0]->id,
                ),
                "amount" => $this->getGBPBalance(),
                "currency" => "GBP",
                "reference" => "Store fiat as BTC in Kraken"
            );
            $accessToken = new \League\OAuth2\Client\Token\AccessToken([
                'access_token' => $this->getPayToken()['access_token']
            ]);
            $client = new \RevolutPHP\Client($accessToken);
            try {
                $client->payments->create($payment);
            } catch (\Exception $e) {
                $discordService->sendMessage('Error sending GBP to Kraken: ' . $e->getMessage());
            }

        }
    }
}
