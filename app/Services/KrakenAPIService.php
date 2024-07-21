<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class KrakenAPIService
{
    private $apiUrl = "https://api.kraken.com";
    private $apiKey;
    private $apiSec;
    private $client;

    public function __construct()
    {
        $this->apiKey =  env('KRAKEN_API_KEY');
        $this->apiSec = env('KRAKEN_PRIVATE_KEY');
        $this->client = new Client();
    }

    private function getKrakenSignature($path, $data, $secret): string
    {
        $postdata = http_build_query($data, '', '&');
        $nonce = $data['nonce'];
        $message = $nonce . $postdata;
        $hash = hash_hmac('sha512', $path . hash('sha256', $nonce . $message, true), base64_decode($secret), true);
        return base64_encode($hash);
    }

    public function krakenRequest($uriPath, $data)
    {
        $data['nonce'] = strval(time() * 1000); // Current nonce
        $signature = $this->getKrakenSignature($uriPath, $data, $this->apiSec);
        try {
            $response = $this->client->post($this->apiUrl . $uriPath, [
                'headers' => [
                    'API-Key' => $this->apiKey,
                    'API-Sign' => $signature,
                ],
                'form_params' => $data,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

