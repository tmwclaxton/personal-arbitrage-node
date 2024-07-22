<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\RevolutAccessToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshRevolutToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $authProvider = new \RevolutPHP\Auth\Provider([
            'clientId' => env('REVOLUT_CLIENT_ID'),
            'privateKey' => 'file://' . storage_path('app/private/RevolutCerts/privatekey.pem'),
            'redirectUri' => env('REVOLUT_REDIRECT_URI'),
            'isSandbox' => false,
        ]);

        // check if there are any RevolutAccessTokens
        if( RevolutAccessToken::all()->count() < 2 && AdminDashboard::all()->first()->revolut_code != null ) {
            // grab admin dashboard
            $adminDashboard = AdminDashboard::all()->first();
            // grab the revolut code
            $revolutCode = $adminDashboard->revolut_code;
            // if there are none, create a new one

            $accessToken = null;
            try {
                $accessToken = $authProvider->getAccessToken('authorization_code', [
                    'code' => $revolutCode
                ]);
            } catch (\Exception $e) {
                $discordService = new \App\Services\DiscordService();
                $discordService->sendMessage('RevolutService: ' . $e->getMessage() );
            }

            RevolutAccessToken::create([
                'access_token' => $accessToken->getToken(),
                'refresh_token' => $accessToken->getRefreshToken(),
                'expires' => $accessToken->getExpires(),
                'values' => $accessToken->getValues()
            ]);

            // set the revolut code to null
            $adminDashboard->revolut_code = null;
            $adminDashboard->save();
        } else {
            // foreach RevolutAccessToken grab and iterate
            $tokens = RevolutAccessToken::all();
            foreach ($tokens as $revolutAccessToken) {
                // convert RevolutAccessToken to AccessToken
                $revolutAccessToken = new \League\OAuth2\Client\Token\AccessToken([
                    'access_token' => $revolutAccessToken->access_token,
                    'refresh_token' => $revolutAccessToken->refresh_token,
                    'expires' => $revolutAccessToken->expires,
                    'resource_owner_id' => $revolutAccessToken->resource_owner_id
                ]);

                // if the token is expired
                if ($revolutAccessToken->hasExpired()) {

                    $newAccessToken = null;
                    try {
                        $newAccessToken = $authProvider->getAccessToken('refresh_token', [
                            'refresh_token' => $revolutAccessToken->getRefreshToken()
                        ]);
                    } catch (\Exception $e) {
                        $discordService = new \App\Services\DiscordService();
                        $discordService->sendMessage('RevolutService: ' . $e->getMessage() );
                    }

                    // find the RevolutAccessToken and update all the fields
                    $revolutAccessToken = RevolutAccessToken::where('refresh_token', $revolutAccessToken->getRefreshToken())->first();
                    $revolutAccessToken->access_token = $newAccessToken->getToken();
                    $revolutAccessToken->expires = $newAccessToken->getExpires();
                    $revolutAccessToken->resource_owner_id = $newAccessToken->getResourceOwnerId();
                    $revolutAccessToken->save();
                }
            }
        }

    }
}
