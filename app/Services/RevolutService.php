<?php

namespace App\Services;

use App\Models\AdminDashboard;
use App\Models\RevolutAccessToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use RevolutPHP\Auth\Provider;

class RevolutService
{
    private Provider $authProvider;

    private AdminDashboard $adminDashboard;

    public function __construct()
    {
        // we need two types of access tokens READ and PAY

        $this->authProvider = new Provider([
            'clientId' => env('REVOLUT_CLIENT_ID'),
            'privateKey' => 'file://' . storage_path('app/private/RevolutCerts/privatecert.pem'),
            'redirectUri' => env('REVOLUT_REDIRECT_URI'),
            'isSandbox' => false,
        ]);

        // grab admin dashboard
        $this->adminDashboard = AdminDashboard::all()->first();
    }

    public function getToken($type) {
        // if revolut code is null, then we need to create a new RevolutAccessToken
        if ($this->adminDashboard->revolut_code == null && RevolutAccessToken::where('type', $type)->count() == 0) {
            // scope PAY
            $url = $this->authProvider->getAuthorizationUrl([
                'scope' => $type,
            ]);

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
                'expires_at' => $accessToken->getExpires(),
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

                $newAccessToken = $this->authProvider->getAccessToken('refresh_token', [
                    'refresh_token' =>  $revolutAccessToken->refresh_token
                ]);

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

    public function currencyExchangeAll($fromCurrency, $toCurrency, $reference = null, $requestId = null) {
        // we need two types of access tokens READ and PAY


        $revArray = $this->getToken('READ');
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
        $accounts = $client->accounts->all();

        // using the GBP account convert all to EUR
        // iterate through the accounts and grab the GBP and EUR accounts
        foreach ($accounts as $account) {
            if ($account->currency == $fromCurrency && $account->balance > 1) {
                $fromAccount = $account;
            }
            if ($account->currency == $toCurrency && $account->balance > 1) {
                $toAccount = $account;
            }
        }
        if (!isset($fromAccount) || !isset($toAccount)) {
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
            'reference' => $reference ?? time() . 'exchange',
            'request_id' => $requestId ?? hash('sha256', time() . 'exchange')
        ];

        $token = null;
        $revArray = $this->getToken('PAY');
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

        $response = $client->exchanges->exchange($exchange);
        // if state is completed then we are good
        if ($response->state == 'completed') {
            $discordService = new DiscordService();
            $discordService->sendMessage('Revolut Currency Exchange Completed From: ' . $fromCurrency . ' To: ' . $toCurrency);
        } else {
            $discordService = new DiscordService();
            $discordService->sendMessage('Revolut Currency Exchange Failed: ' . $response);
        }
    }

    public function getTransactions()
    {
        $revArray = $this->getToken('READ');
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
        return $client->transactions->all();
    }

}
