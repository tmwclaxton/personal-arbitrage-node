<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class RevolutService
{
    protected $client;
    protected $apiUrl;
    protected $privateKey;
    protected $redirectUri;
    protected $clientId;

    public function __construct()
    {
        $this->privateKey = env('REVOLUT_PRIVATE_KEY');
        $this->redirectUri = env('REVOLUT_REDIRECT_URI');
        $this->clientId = env('REVOLUT_CLIENT_ID');
        $this->apiUrl = env('REVOLUT_SANDBOX', true) ? 'https://sandbox-b2b.revolut.com/api/1.0' : 'https://b2b.revolut.com/api/1.0';

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    private function getAccessToken()
    {
        // Retrieve access token logic here
        // This function should handle OAuth authentication to get an access token
        // Assuming you have a function or a way to get the access token
    }

    public function getRecentTransactions($count = 10)
    {
        try {
            $response = $this->client->get('/transaction', [
                'query' => ['count' => $count]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            // Handle error
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getCurrentFunds($currency)
    {
        try {
            $response = $this->client->get('/account');

            $accounts = json_decode($response->getBody(), true);
            foreach ($accounts as $account) {
                if ($account['currency'] === strtoupper($currency)) {
                    return $account['balance'];
                }
            }

            return [
                'error' => true,
                'message' => 'Currency not found',
            ];
        } catch (RequestException $e) {
            // Handle error
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

    }
    // convert one entire account to a different currency
    function convertAccount($fromCurrency, $toCurrency, $amount) {
        try {
            $response = $this->client->post('/account/convert', [
                'json' => [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'amount' => $amount,
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            // Handle error
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    // send money to a recipient
    function sendMoney($recipientId, $currency, $amount) {
        try {
            $response = $this->client->post('/transfer', [
                'json' => [
                    'request_id' => uniqid(),
                    'source_account_id' => $this->getAccountId($currency),
                    'target_account_id' => $recipientId,
                    'amount' => $amount,
                    'currency' => $currency,
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            // Handle error
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}
