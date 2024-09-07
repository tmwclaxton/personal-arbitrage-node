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
        if ($this->ip === null || filter_var($this->ip, FILTER_VALIDATE_IP) === false) {
            $adminDashboard = AdminDashboard::all()->first();
            $this->ip = $adminDashboard->umbrel_ip;
        }

    }

    // ping the umbrel server || bear needs to be other proxy token
    // public function ping()
    // {
    //     $adminDashboard = AdminDashboard::all()->first();
    //     $response = Http::withHeaders([
    //         'Authorization' => 'Bearer ' . $adminDashboard->umbrel_token,
    //         'Cookie' => 'UMBREL_PROXY_TOKEN=' . $adminDashboard->umbrel_token,
    //     ])->get($this->ip . ':80/trpc/user.isLoggedIn');
    //
    //     // if result -> data = false then call resetProxyToken
    //     $response = json_decode($response->getBody(), true);
    //     dd($response);
    //     if ($response['result']['data'] === false) {
    //         $this->resetProxyToken();
    //         return "Token resetted!";
    //     }
    //     return "Token is still valid!";
    // }

    public function resetProxyToken()
    {
        $adminDashboard = AdminDashboard::all()->first();
        $params = [
            'password' => $adminDashboard->umbrel_password,
        ];
        if ($adminDashboard->umbrel_totp_key !== null) {
            $otp = TOTP::createFromSecret($adminDashboard->umbrel_totp_key);
        }
        if (isset($otp)) {
            $params['totpToken'] = $otp->now();
        }

        $response = Http::post($this->ip . ':80/trpc/user.login', $params);



        $proxyToken = $response->cookies()->getCookieByName('UMBREL_PROXY_TOKEN')->getValue();

        $adminDashboard = AdminDashboard::all()->first();
        $adminDashboard->umbrel_token = $proxyToken;
        $adminDashboard->save();

        return $proxyToken;
    }
}
