<?php

namespace App\Services;

use App\Models\MonzoAccessToken;
use GuzzleHttp\Client;

class MonzoService
{

    private $user_id;
    private $access_token;
    private $account_id;
    private $refresh_token;
    private $client_id;
    private $client_secret;


    private $client;


    public function __construct()
    {
        $this->user_id = env('MONZO_USER_ID');
        $this->access_token = env('MONZO_ACCESS_TOKEN');
        $this->account_id = env('MONZO_ACCOUNT_ID');
        $this->refresh_token = env('MONZO_REFRESH_TOKEN');
        $this->client_id = env('MONZO_CLIENT_ID');
        $this->client_secret = env('MONZO_CLIENT_SECRET');
        $this->client = new Client();
    }

    public function redirectUserToMonzo() {

        $state = bin2hex(random_bytes(16));

        $url = 'https://auth.monzo.com/?' . http_build_query([
            'client_id' => $this->client_id,
            'redirect_uri' => 'https://www.vidgaze.tv',
            'response_type' => 'code',
            // 'state' => $state
        ]);
        return $url;
    }

    // exchange code for access token and refresh token
    public function exchangeCode($code) {
        $response = $this->client->post('https://api.monzo.com/oauth2/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => 'https://www.vidgaze.tv',
                'code' => $code
            ]
        ]);

        $decodedResponse = json_decode($response->getBody()->getContents(), true);
        $monzoAccessToken = new MonzoAccessToken();
        $monzoAccessToken->access_token = $decodedResponse['access_token'];
        $monzoAccessToken->client_id = $decodedResponse['client_id'];
        $monzoAccessToken->expires = time() + $decodedResponse['expires_in'];
        $monzoAccessToken->refresh_token = $decodedResponse['refresh_token'];
        $monzoAccessToken->type = $decodedResponse['token_type'];
        $monzoAccessToken->user_id = $decodedResponse['user_id'];

        $monzoAccessToken->save();

        return $monzoAccessToken;
    }

    public function refreshAccessToken(MonzoAccessToken $monzoAccessToken) {
        $response = $this->client->post('https://api.monzo.com/oauth2/token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => $monzoAccessToken->client_id,
                'client_secret' => $this->client_secret,
                'refresh_token' => $monzoAccessToken->refresh_token
            ]
        ]);

        $decodedResponse = json_decode($response->getBody()->getContents(), true);
        $monzoAccessToken->access_token = $decodedResponse['access_token'];
        $monzoAccessToken->client_id = $decodedResponse['client_id'];
        $monzoAccessToken->expires = time() + $decodedResponse['expires_in'];
        $monzoAccessToken->refresh_token = $decodedResponse['refresh_token'];
        $monzoAccessToken->type = $decodedResponse['token_type'];
        $monzoAccessToken->user_id = $decodedResponse['user_id'];

        $monzoAccessToken->save();

        return $monzoAccessToken;
    }

    public function getBalance() {
        $monzoAccessToken = MonzoAccessToken::where('user_id', $this->user_id)->first();

        $response = $this->client->get('https://api.monzo.com/balance', [
            'headers' => ['Authorization' => 'Bearer ' . $monzoAccessToken->access_token],
            'query' => ['account_id' => $this->account_id]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function sendMoney($destination_account_number, $destination_sort_code, $amount, $reference)
    {
        $monzoAccessToken = MonzoAccessToken::where('user_id', $this->user_id)->first();

        $response = $this->client->post('https://api.monzo.com/payments', [
            'headers' => ['Authorization' => 'Bearer ' . $monzoAccessToken->access_token],
            'json' => [
                'account_id' => $this->account_id,
                'destination_account_number' => $destination_account_number,
                'destination_sort_code' => $destination_sort_code,
                'amount' => $amount, // amount in pence
                'reference' => $reference,
                'dedupe_id' => bin2hex(random_bytes(16)) // a unique identifier for the transaction
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
