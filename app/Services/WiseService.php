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
    private function _makeRequest(string $method, string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;

        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept'        => 'application/json',
                ],
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
}
