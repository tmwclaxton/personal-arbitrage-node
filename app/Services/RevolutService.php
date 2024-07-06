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

            redirect($url);

            dd('redirecting to revolut');

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
                'expires' => $accessToken->getExpires(),
            ]);

            // set the revolut code to null
            $this->adminDashboard->revolut_code = null;
            $this->adminDashboard->save();
        } else {
            $revolutAccessToken = RevolutAccessToken::where('type', $type)->first();

            // if the token is expired
            if ($revolutAccessToken->hasExpired()) {

                $newAccessToken = $this->authProvider->getAccessToken('refresh_token', [
                    'refresh_token' => $revolutAccessToken->getRefreshToken()
                ]);

                $revolutAccessToken = RevolutAccessToken::where('refresh_token', $revolutAccessToken->getRefreshToken())->first();
                $revolutAccessToken->access_token = $newAccessToken->getToken();
                $revolutAccessToken->expires = $newAccessToken->getExpires();
                $revolutAccessToken->save();

            }

            // convert RevolutAccessToken to AccessToken
            $revolutAccessToken = new \League\OAuth2\Client\Token\AccessToken([
                'access_token' => $revolutAccessToken->access_token,
                'refresh_token' => $revolutAccessToken->refresh_token,
                'expires' => $revolutAccessToken->expires,
            ]);
        }

        return $revolutAccessToken->access_token;
    }

    public function getReadToken() {
        return $this->getToken('READ');
    }
    public function getPayToken() {
        return $this->getToken('PAY');
    }


}
