<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WiseService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('WISE_API_KEY');
        $this->baseUrl = 'https://api.transferwise.com';
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
            $response = $this->client->request($method, $url, [
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

    /**
     * Get recent payment activities for a given profile ID.
     *
     * @param string $profileId
     * @return array
     * @throws \Exception
     */
    public function getActivities(string $profileId): array
    {
        return $this->_makeRequest('GET', "/v1/profiles/{$profileId}/activities");
    }

    /**
     * List all transfers for a given profile ID.
     *
     * @param string $profileId
     * @return array
     * @throws \Exception
     */
    public function listTransfers(string $profileId): array
    {
        //curl -X GET https://api.sandbox.transferwise.tech/v1/transfers
        return $this->_makeRequest('GET', "/v1/transfers");
    }


    public function getTransfer(string $transferId): array
    {
        return $this->_makeRequest('GET', "/v1/transfers/{$transferId}");
    }

    public function getBalances(string $profileId): array
    {
        return $this->_makeRequest('GET', "/v4/profiles/{$profileId}/balances?types=STANDARD");
    }


    public function getBalanceStatement(string $profileId, string $balanceId): array
    {
        return $this->_makeRequest('GET', "/v1/profiles/{$profileId}/balance-statements/{$balanceId}/statement.json?currency=GBP&type=COMPACT");
    }


    public function createQuote($profileId, $sourceCurrency, $sourceAmount, $sourceAccount, $targetCurrency): array
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
                "transferNature" => "MOVING_MONEY_BETWEEN_OWN_ACCOUNTS"
            ]
        ];

        return $this->_makeRequest("POST", "/v3/profiles/{$profileId}/quotes", $params);
    }

    public function getQuoteByID($profileId, $quoteId): array
    {
        return $this->_makeRequest('GET', "/v3/profiles/{$profileId}/quotes/{$quoteId}");
    }

    public function convertAcrossBalAccounts($profileId, $quoteId, $sourceBalanceId, $targetBalanceId): array {
        $params = [
            'quoteId' => $quoteId,
            'sourceBalanceId' => $sourceBalanceId,
            'targetBalanceId' => $targetBalanceId
        ];
        $extraHeaders = [
            'Content-Type' => 'application/json',
            'X-idempotence-uuid' => uniqid()
        ];

        return $this->_makeRequest('POST', "/v2/profiles/{$profileId}/balance-movements", $params, $extraHeaders);
    }
}
