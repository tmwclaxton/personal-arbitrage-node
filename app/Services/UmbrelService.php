<?php

namespace App\Services;

use App\Models\AdminDashboard;
use http\Client;
use Illuminate\Support\Facades\Http;
use OTPHP\TOTP;

class UmbrelService
{
    private $ip;

    // create client for it
    public function __construct()
    {
        // resolve hostname to local ip
        $hostname = env('UMBREL_URL');
        $this->ip = gethostbyname($hostname);

        // if ip is not found i.e contains letters
        if (filter_var($this->ip, FILTER_VALIDATE_IP) === false) {
            $this->ip = env('UMBREL_IP');
        }

    }

    // ping the umbrel server
    public function ping()
    {
        $adminDashboard = AdminDashboard::all()->first();
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $adminDashboard->umbrel_token,
            'Cookie' => 'UMBREL_PROXY_TOKEN=' . $adminDashboard->umbrel_token,
        ])->get($this->ip . ':80/trpc/user.isLoggedIn');

        // if result -> data = false then call resetProxyToken
        $response = json_decode($response->getBody(), true);
        if ($response['result']['data'] === false) {
            $this->resetProxyToken();
            return "Token resetted!";
        }
        return "Token is still valid!";
    }

    public function resetProxyToken()
    {
        $otp = TOTP::createFromSecret(env("UMBREL_TOTP_KEY"));
        $response = Http::post($this->ip . ':80/trpc/user.login', [
            'password' => env("UMBREL_PASSWORD"),
            'totpToken' => $otp->now(),
        ]);
        $response = json_decode($response->getBody(), true);

        $proxyToken = $response['result']['data'];

        $adminDashboard = AdminDashboard::all()->first();
        $adminDashboard->umbrel_token = $proxyToken;
        $adminDashboard->save();

        return $proxyToken;
    }
}
