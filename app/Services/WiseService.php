<?php

namespace App\Services;

use Crypt_GPG;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use phpseclib3\Crypt\RSA;
use Ramsey\Uuid\Uuid;

class WiseService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;

    protected $profileID;

    protected $httpClient;

    protected $privateKey;
    protected $publicKey;
    protected $x509;

    public function __construct()
    {
        // check if the private key, public key, and x509 certificate exists
        if ( !file_exists(storage_path('app/private/WiseCerts/privatekey.pem')) ) {
            // create the directory if it does not exist
            if (!file_exists(storage_path('app/private/WiseCerts'))) {
                mkdir(storage_path('app/private/WiseCerts'), 0777, true);
            }


            // generate x509 certificate
            $pgpService = new PgpService();
            $response = $pgpService->generateX509Certificates();

            // save the private key, public key, and x509 certificate to app/storage/private
            $this->privateKey = $response['private_key'];
            $this->publicKey = $response['public_key'];
            $this->x509 = $response['x509'];

            // save the private key, public key, and x509 certificate to app/storage/private
            file_put_contents(storage_path('app/private/WiseCerts/privatekey.pem'), $this->privateKey);
            file_put_contents(storage_path('app/private/WiseCerts/publickey.pem'), $this->publicKey);
            file_put_contents(storage_path('app/private/WiseCerts/x509.pem'), $this->x509);
        } else {
            $this->privateKey = file_get_contents(storage_path('app/private/WiseCerts/privatekey.pem'));
            $this->publicKey = file_get_contents(storage_path('app/private/WiseCerts/publickey.pem'));
            $this->x509 = file_get_contents(storage_path('app/private/WiseCerts/x509.pem'));
        }

        $this->httpClient = new Client();
        $this->apiKey = env('WISE_API_KEY');
        $this->baseUrl = 'https://api.transferwise.com';
        // set up wise client
        $this->client = new \TransferWise\Client(
            [
                "token" => env('WISE_API_KEY'),
                "profile_id" => env('WISE_PROFILE_ID'),
            ]
        );

        // $profiles = $this->client->profiles->all();
        // $profileID = $profiles[0]['id'];
        $this->profileID = env('WISE_PROFILE_ID');
    }

    public function getClient() {
        return $this->client;
    }

    public function getProfile($profileId) {
        // GET /v2/profiles/{{profileId}}
        return $this->_makeRequest('GET', "/v1/me");
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
    private function _makeRequest(string $method, string $endpoint, array $params = [], array $extraHeaders = [], bool $retry = true): array
    {
        $url = $this->baseUrl . $endpoint;

        $authHeaders = [];
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
            $headers = $e->getResponse()->getHeaders();
            // if the headers contain x-2fa-approval and x-2fa-approval-result then set authHeaders
            if (isset($headers['x-2fa-approval']) && isset($headers['x-2fa-approval-result']) && $retry) {
                $authHeaders = [
                    'X-2FA-Approval' => $headers['x-2fa-approval'][0],
                    'X-2FA-Approval-Result' => $headers['x-2fa-approval-result'][0]
                ];
                // get status of the ott
                $ott = $authHeaders['X-2FA-Approval'];
                $ottStatus = $this->getOttStatus($ott);
                $primaryChallenge = $ottStatus['oneTimeTokenProperties']['challenges'][0]['primaryChallenge'];
                $alternatives = $ottStatus['oneTimeTokenProperties']['challenges'][0]['alternatives'];

                // if primary challenge is signature
                if ($primaryChallenge['type'] === "SIGNATURE") {
                    $signedOTT = $this->signedOTT($ott);

                    // retry the request with the signed ott as a header X-Signature
                    $extraHeaders['x-2fa-approval'] = $ott;
                    $extraHeaders['X-Signature'] = $signedOTT;
                    $request = $this->_makeRequest($method, $endpoint, $params, $extraHeaders, false);

                } else if ($primaryChallenge['type'] === "PIN") {
                    $discordService = new DiscordService();
                    $discordService->sendMessage('Wise PIN Challenge not implemented yet');
                    // $verifyPin = $this->verifyPin($ott);
                }
            } else {
                // throw new \Exception("Error making request: " . $e->getMessage());
                dd($e);
            }

            if (isset($request)) {
                return $request;
            }
        }


    }

    public function signedOTT($ott) {
        $privateKey = RSA::loadPrivateKey($this->privateKey);
        $signedOTT = $privateKey->sign($ott);
        return base64_encode($signedOTT);
    }

    // ott status
    public function getOttStatus($ott) {
        $extraHeaders = [
            'One-Time-Token' => $ott
        ];

        return $this->_makeRequest('GET', "/v1/one-time-token/status", [], $extraHeaders);
   }

    // verify pin
    public function verifyPin($ott) {
        $params = [
            'pin' => env('WISE_PIN'),
        ];

        $extraHeaders = [
            'Content-Type' => 'application/jose+json',
            'X-TW-JOSE-Method' => 'jwe',
            'Accept' => 'application/jose+json',
            'Accept-Encoding' => '*',
            'One-Time-Token' => $ott
        ];

        return $this->_makeRequest('POST', "/v1/one-time-token/pin/verify", $params, $extraHeaders);
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


    public function createQuote($sourceCurrency, $sourceAmount, $sourceAccount, $targetCurrency,
                                $targetAccount = null, $transferNature = null, $payOut = "BALANCE"
    ): array
    {

        $params = [
            "guaranteedTargetAmount" => false,
            "payInId" => $sourceAccount,
            "payInMethod" => "BALANCE",
            "payOut" => $payOut,
            "preferredPayIn" => "BALANCE",
            "sourceAmount" => $sourceAmount,
            "sourceCurrency" => $sourceCurrency,
            "targetCurrency" => $targetCurrency,
            "type" => "SPOT",
        ];

        if ($targetAccount) {
            $params['targetAccount'] = $targetAccount;
        }

        if ($transferNature) {
            $params['paymentMetadata'] = [
                "transferNature" => $transferNature
            ];
        }

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

    public function transferToRecipient($quoteId, $recipientId, $reference): array {
        $params = [
            'quoteUuid' => $quoteId,
            'targetAccount' => $recipientId,
            'customerTransactionId' => Uuid::uuid4()->toString(),
            'details' => [
                'reference' => $reference
            ]
        ];

        return $this->_makeRequest('POST', "/v1/transfers", $params);
    }

    public function fundTransfer($transferId): array
    {
        $params = [
            'type' => 'BALANCE'
        ];

        return $this->_makeRequest('POST', "/v3/profiles/{$this->profileID}/transfers/{$transferId}/payments", $params);
    }


    public function getGBPAccount() {
        $accounts = $this->getBalances();
        foreach ($accounts as $account) {
            if ($account['currency'] == 'GBP') {
                return $account;
            }
        }
        return null;
    }

    public function getGBPBalance() {
        $gbpAccount = $this->getGBPAccount();
        return $gbpAccount['amount']['value'];
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

        $quote = $wiseService->createQuote($fromCurrency, $fromAccount['amount']['value'], $fromAccount['id'], $toCurrency, null, "MOVING_MONEY_BETWEEN_OWN_ACCOUNTS");
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
