<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Ramsey\Uuid\Uuid;

class WiseService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;

    protected $profileID;

    protected $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->apiKey = env('WISE_API_KEY');
        $this->baseUrl = 'https://api.transferwise.com';
        // set up wise client
        $this->client = new \TransferWise\Client(
            [
                "token" => env('WISE_API_KEY'),
                "profile_id" => "test",
            ]
        );

        $profiles = $this->client->profiles->all();
        $profileID = $profiles[0]['id'];
        $this->profileID = $profileID;
    }

    public function getClient() {
        return $this->client;
    }

    /**
     * Make an authenticated request to the Wise API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @return array
     * @throws \Exception
     */
    private function _makeRequest(string $method, string $endpoint, array $params = [], array $extraHeaders = []): array
    {
        $url = $this->baseUrl . $endpoint;

        try {
            $response = $this->httpClient->request($method, $url, [
                'headers' => array_merge([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept'        => 'application/json',
                ], $extraHeaders),
                'json' => $params,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode >= 200 && $statusCode < 300) {
                return json_decode($body, true);
            } else {
                throw new \Exception("Request failed with status code: {$statusCode}");
            }
        } catch (RequestException $e) {
            throw new \Exception("Error making request: " . $e->getMessage());
        }
    }


    public function getActivities(): array
    {
        return $this->_makeRequest('GET', "/v1/profiles/{$this->profileID}/activities");
    }

    public function listTransfers(): array
    {
        //curl -X GET https://api.sandbox.transferwise.tech/v1/transfers
        return $this->_makeRequest('GET', "/v1/transfers");
    }


    public function getTransfer(string $transferId): array
    {
        return $this->_makeRequest('GET', "/v1/transfers/{$transferId}");
    }

    public function getBalances(): array
    {
        return $this->_makeRequest('GET', "/v4/profiles/{$this->profileID}/balances?types=STANDARD");
    }


    public function getBalanceStatement(string $balanceId): array
    {
        return $this->_makeRequest('GET', "/v1/profiles/{$this->profileID}/balance-statements/{$balanceId}/statement.json?currency=GBP&type=COMPACT");
    }


    public function createQuote($sourceCurrency, $sourceAmount, $sourceAccount, $targetCurrency, $transferNature = "MOVING_MONEY_BETWEEN_OWN_ACCOUNTS"): array
    {

        $params = [
            "guaranteedTargetAmount" => false,
            "payInId" => $sourceAccount,
            "payInMethod" => "BALANCE",
            "payOut" => "BALANCE",
            "preferredPayIn" => "BALANCE",
            "sourceAmount" => $sourceAmount,
            "sourceCurrency" => $sourceCurrency,
            "targetCurrency" => $targetCurrency,
            "type" => "SPOT",
            "paymentMetadata" => [
                "transferNature" => $transferNature
            ]
        ];

        return $this->_makeRequest("POST", "/v3/profiles/{$this->profileID}/quotes", $params);
    }

    public function getQuoteByID($quoteId): array
    {
        return $this->_makeRequest('GET', "/v3/profiles/{$this->profileID}/quotes/{$quoteId}");
    }

    public function convertAcrossBalAccounts($quoteId, $sourceBalanceId, $targetBalanceId): array {
        $params = [
            'quoteId' => $quoteId,
            'sourceBalanceId' => $sourceBalanceId,
            'targetBalanceId' => $targetBalanceId
        ];

        $extraHeaders = [
            'X-idempotence-uuid' => Uuid::uuid4()->toString()
        ];

        return $this->_makeRequest('POST', "/v2/profiles/{$this->profileID}/balance-movements", $params, $extraHeaders);
    }

    public function getGBPAccount() {
        $accounts = $this->getBalances();
        foreach ($accounts as $account) {
            if ($account['currency'] == 'GBP') {
                return $account;
            }
        }
    }

    public function currencyExchangeAll($fromCurrency, $toCurrency, $reference = null, $requestId = null, $minAmount = 5) {

        $wiseService = new \App\Services\WiseService();
        $response = $wiseService->getBalances();

        // iterate through the accounts and grab the GBP and EUR accounts
        $fromAccount = null;
        $toAccount = null;
        foreach ($response as $account) {
            if ($account['currency'] == $fromCurrency && $account['amount']['value'] > $minAmount) {
                $fromAccount = $account;
            }
            if ($account['currency'] == $toCurrency) {
                $toAccount = $account;
            }
        }

        if (!isset($fromAccount) || !isset($toAccount)) {
            return;
        }

        $quote = $wiseService->createQuote($fromCurrency, $fromAccount['amount']['value'], $fromAccount['id'], $toCurrency, );
        $quoteID = $quote['id'];
        $convert = $wiseService->convertAcrossBalAccounts($quoteID, $fromAccount['id'], $toAccount['id']);

        if ($convert['state'] == 'COMPLETED') {
            $discordService = new DiscordService();
            $discordService->sendMessage('Wise Currency Exchange Completed of ' . $convert['targetAmount']['value'] . ' ' . $fromCurrency . ' to ' . $toCurrency);
        } else {
            $discordService = new DiscordService();
            $discordService->sendMessage('Wise Currency Exchange Failed: ' . json_encode($convert) );
        }
    }


    public function getRecipientAccounts($currency): array
    {
        return $this->_makeRequest('GET', "/v2/accounts?profileId={$this->profileID}&currency={$currency}");
    }
}
