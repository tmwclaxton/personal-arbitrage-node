<?php

namespace App\WorkerClasses;

use App\Console\Commands\UpdateOffers;
use App\Http\Controllers\OfferController;
use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\PaymentMethod;
use App\Models\PostedOfferTemplate;
use App\Models\Robot;
use App\Models\Transaction;
use App\Services\SlackService;
use App\Services\PgpService;
use Exception;
use Faker\Factory;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Message\Message;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;
use WebSocket\Server;

class Robosats
{

    // list of different providers [satstralia, temple, lake, veneto]
    public array $providers = [
        'satstralia' => 'satstralia',
        'temple' => 'temple',
        'lake' => 'lake',
        'veneto' => 'veneto',
        'exp' => 'exp'
    ];

    public function getWsHost() {
        $adminDashboard = AdminDashboard::all()->first();
        return 'ws://' . $adminDashboard->umbrel_ip . ':12596';
    }

    public function getHost() {
        $adminDashboard = AdminDashboard::all()->first();
        return 'http://' . $adminDashboard->umbrel_ip . ':12596';
    }



    public const CURRENCIES = [
        '1' => 'USD',
        '2' => 'EUR',
        '3' => 'JPY',
        '4' => 'GBP',
        '5' => 'AUD',
        '6' => 'CAD',
        '7' => 'CHF',
        '8' => 'CNY',
        '9' => 'HKD',
        '10' => 'NZD',
        '11' => 'SEK',
        '12' => 'KRW',
        '13' => 'SGD',
        '14' => 'NOK',
        '15' => 'MXN',
        '16' => 'BYN',
        '17' => 'RUB',
        '18' => 'ZAR',
        '19' => 'TRY',
        '20' => 'BRL',
        '21' => 'CLP',
        '22' => 'CZK',
        '23' => 'DKK',
        '24' => 'HRK',
        '25' => 'HUF',
        '26' => 'INR',
        '27' => 'ISK',
        '28' => 'PLN',
        '29' => 'RON',
        '30' => 'ARS',
        '31' => 'VES',
        '32' => 'COP',
        '33' => 'PEN',
        '34' => 'UYU',
        '35' => 'PYG',
        '36' => 'BOB',
        '37' => 'IDR',
        '38' => 'ANG',
        '39' => 'CRC',
        '40' => 'CUP',
        '41' => 'DOP',
        '42' => 'GHS',
        '43' => 'GTQ',
        '44' => 'ILS',
        '45' => 'JMD',
        '46' => 'KES',
        '47' => 'KZT',
        '48' => 'MYR',
        '49' => 'NAD',
        '50' => 'NGN',
        '51' => 'AZN',
        '52' => 'PAB',
        '53' => 'PHP',
        '54' => 'PKR',
        '55' => 'QAR',
        '56' => 'SAR',
        '57' => 'THB',
        '58' => 'TTD',
        '59' => 'VND',
        '60' => 'XOF',
        '61' => 'TWD',
        '62' => 'TZS',
        '63' => 'XAF',
        '64' => 'UAH',
        '65' => 'EGP',
        '66' => 'LKR',
        '67' => 'MAD',
        '68' => 'AED',
        '69' => 'TND',
        '70' => 'ETB',
        '71' => 'GEL',
        '72' => 'UGX',
        '73' => 'RSD',
        '74' => 'IRT',
        '75' => 'BDT',
        '76' => 'ALL',
        '300' => 'XAU',
        '1000' => 'BTC'
    ];


    protected array $headers = [
                "Host" => "192.168.0.18:12596",
                "Accept" => "*/*",
                "Accept-Language" => "en-GB,en;q=0.5",
                "Accept-Encoding" => "gzip, deflate",
                "Referer" => "http://192.168.0.18:12596/",
                "Content-Type" => "application/json",
                "Connection" => "keep-alive",
                "Priority" => "u=4",
                "Pragma" => "no-cache",
                "Cache-Control" => "no-cache",
                // "Origin" => "http://192.168.0.18:12596",
    ];

    public function getHeaders($offer = null)
    {
        if ($offer) {
            $tokenSha256 = $offer->robots()->first()->sha256;
            $tokenSha256 = str_replace("\n", '', $tokenSha256);
            $tokenSha256 = str_replace("\r", '', $tokenSha256);
            $this->headers["Authorization"] = "Token " . $tokenSha256;
            $this->headers["Priority"] = "u=1";
            if ($offer->status < 3) {
                if ($offer->robots()->first()) {
                    $offer->robotTokenBackup = $offer->robots()->first()->token;
                    $offer->save();
                }
            }
            // remove new lines and \r
        }
        $adminDash = AdminDashboard::all()->first();
        $this->headers["Cookie"] = "UMBREL_PROXY_TOKEN=" . $adminDash->umbrel_token;
        $this->headers["User-Agent"] = Factory::create()->userAgent;
        $this->headers["DNT"] = "1";
        // $this->headers["Accept-Language"] = array_rand(['en-US', 'en']) . ';q=0.9,en;q=0.8';
        $acceptLanguages = ['en-US', 'en'];
        $this->headers["Accept-Language"] = $acceptLanguages[array_rand($acceptLanguages)] . ';q=0.9,en;q=0.8';
        $referredLocations = [
            $this->getHost() . '/offers/',
            $this->getHost() . 'order/satstralia/' . rand(1000, 9999),
            $this->getHost() . 'order/temple/' . rand(1000, 9999),
            $this->getHost() . 'order/lake/' . rand(1000, 9999),
            $this->getHost() . 'order/veneto/' . rand(1000, 9999)
        ];
        $this->headers['Referer'] = $referredLocations[array_rand($referredLocations)];

        return $this->headers;
    }



    public function createRobots($offer = null) {
        if ($offer) {
            // check if the offer already has a robot
            $robots = Robot::where('offer_id', $offer->id)->get();
            if ($robots->count() > 0 || $offer->robots_created) {
                return $robots;
            }
        }

        $generator = new ComputerPasswordGenerator();
        $generator
            ->setOptionValue(ComputerPasswordGenerator::OPTION_UPPER_CASE, true)
            ->setOptionValue(ComputerPasswordGenerator::OPTION_LOWER_CASE, true)
            ->setOptionValue(ComputerPasswordGenerator::OPTION_NUMBERS, true)
            ->setOptionValue(ComputerPasswordGenerator::OPTION_SYMBOLS, true)
            ->setOptionValue(ComputerPasswordGenerator::OPTION_LENGTH, 36)
        ;

        $generatedToken = $generator->generatePassword();
        $sha256 = hash('sha256', $generatedToken);

        $b91 = new \Katoga\Allyourbase\Base91();
        $b91Token = $b91->encode(pack('H*', $sha256));

        $pgpService = new PgpService();
        $keyPair = $pgpService->generate_keypair($generatedToken);

        $private_key = $keyPair['private_key'];
        $public_key = $keyPair['public_key'];
        // replace \n with \
        // $privateKey = str_replace("\n", '\\', $privateKeyUnescaped);
        // $publicKey = str_replace("\n", '\\', $publicKeyUnescaped);

        // ideal format for authentication
        $authentication = 'Token ' . $b91Token . ' | Public ' . $public_key . ' | Private ' . $private_key;
        // remove new lines and \r
        $authentication = str_replace("\n", '', $authentication);
        $authentication = str_replace("\r", '', $authentication);
        // at the end of ----- add \\
        $authentication = str_replace('-----BEGIN PGP PUBLIC KEY BLOCK-----', '-----BEGIN PGP PUBLIC KEY BLOCK-----\\\\', $authentication);
        $authentication = str_replace('-----END PGP PUBLIC KEY BLOCK-----', '\\-----END PGP PUBLIC KEY BLOCK-----\\', $authentication);
        $authentication = str_replace('-----BEGIN PGP PRIVATE KEY BLOCK-----', '-----BEGIN PGP PRIVATE KEY BLOCK-----\\\\', $authentication);
        $authentication = str_replace('-----END PGP PRIVATE KEY BLOCK-----', '\\-----END PGP PRIVATE KEY BLOCK-----\\\\', $authentication);

        // check if the provider is online
        $adminDashboard = AdminDashboard::all()->first();
        $providers = json_decode($adminDashboard->provider_statuses, true);
        $onlineProviders = [];

        if ($offer && (!array_key_exists($offer->provider, $providers) || $providers[$offer->provider] == 'offline')) {
            return 'Provider is offline';
        }

        foreach ($providers as $provider => $status) {
            if ($status != null && $status != 'offline') {
                $onlineProviders[] = $provider;
            }
        }

        if ($offer && !in_array($offer->provider, $onlineProviders)) {
            return 'Main provider is offline';
        }

        foreach ($onlineProviders as $provider) {
            $url = $this->getHost() . '/mainnet/' . $provider . '/api/robot/';
            $headers = $this->getHeaders();
            $headers['Authorization'] = $authentication;
            $headers['Referer'] = $this->getHost() . '/robot/';
            $headers['Priority'] = 'u=1';

            try {
                // Make the GET request with HTTP 1.1
                $response = Http::withHeaders($headers)
                    ->withOptions(['version' => '1.1'])
                    ->timeout(30)
                    ->get($url);
                // dd(json_decode($response->body()));

            } catch (\Exception $e) {
                // Return or log the exception
                $slackService = new SlackService();
                $slackService->sendMessage('Error creating robot: ' . $e->getMessage() . ' on ' . $provider);
                // unlock the offer // this allows it to be retried
                // if the robot creation failed for the same provider as the offer then return otherwise continue
                if ($offer && $provider == $offer->provider) {
                    // because we haven't accepted the offer it is acceptable to delete the robots
                    $offer->robots()->delete();
                    $offer->job_locked = false;
                    $offer->save();
                    return $e->getMessage();
                } else {
                    continue;
                }
            }

            $json = json_decode($response->body(), true);
            if (empty($json) || $json == null) {
                continue;
            }

            $robot = new Robot();
            $robot->provider = $provider;
            if ($offer) {
                $robot->offer_id = $offer->id;
            }
            $robot->token = $generatedToken;
            $robot->sha256 = $b91Token;
            $robot->nickname = $json['nickname'];
            $robot->hash_id = $json['hash_id'];
            $robot->public_key = $public_key;
            $robot->private_key = $private_key;
            // $robot->encrypted_private_key = $json['encrypted_private_key'];
            $robot->earned_rewards = $json['earned_rewards'];
            $robot->wants_stealth = $json['wants_stealth'];
            // convert last_login to datetime from 2024-06-28T23:39:02.732620Z to 2024-06-28 23:39:02
            $robot->last_login = date('Y-m-d H:i:s', strtotime($json['last_login']));
            $robot->tg_enabled = $json['tg_enabled'];
            $robot->tg_token = $json['tg_token'];
            $robot->tg_bot_name = $json['tg_bot_name'];
            $robot->save();
        }
        if ($offer) {
            $offer->robots_created = true;
            $offer->save();
        }

        return Robot::where('token', $generatedToken)->get();
    }


    public function request($endpoint, $offer = null) {
        $providers = $this->providers;
        $urlStart = $this->getHost() . '/mainnet/';
        $headers = $this->getHeaders($offer);

        $responses = [];
        foreach ($providers as $provider) {
            $url = $urlStart . $provider . '/' . $endpoint;

            try {
                $response = Http::withHeaders($headers)->timeout(30)->get($url);
            } catch (\Exception $e) {
                continue;
            }

            if ($response->failed()) {
                continue;
            }

            // convert response to json
            $responses[$provider] = json_decode($response->body(), true);
        }

        return $responses;


    }


    public function getCurrentPrices()
    {
        // $provider = array_rand($this->providers);
        // choose a provider that isn't temple
        $provider = array_rand(array_diff($this->providers, ['temple']));
        $url = $this->getHost() . '/mainnet/' . $this->providers[$provider] . '/api/limits/';

        $response = Http::withHeaders($this->getHeaders())->timeout(30)->get($url);

        $prices = json_decode($response->body(), true);

        return $prices;
    }


    public function getBookOffers() {
        $bookOffers = $this->request('api/book/');

        // {"id":9521,"created_at":"2024-06-26T17:31:31.026243Z","expires_at":"2024-06-27T17:30:31.026243Z","type":1,"currency":1,"amount":null,"has_range":true,"min_amount":"300.00000000","max_amount":"3000.00000000","payment_method":"Revolut Zelle Strike","is_explicit":false,"premium":"5.00","satoshis":null,"maker":1742,"escrow_duration":28800,"bond_size":"2.00","latitude":null,"longitude":null,"maker_nick":"UpperKitchen773","maker_hash_id":"1c32ad9f75ea44901ef6dff8430e4f3a12e40c34c269c4f9fed412bdee278f61","satoshis_now":4669218,"price":64251,"maker_status":"Active"},

        // for each provider filter into 2 subgroups of type 0 and type 1, type 1 is for buying and type 0 is for selling
        $buyOffers = [];
        $sellOffers = [];

        foreach ($bookOffers as $provider => $offers) {
            $buyOffers[$provider] = [];
            $sellOffers[$provider] = [];
            foreach ($offers as $offer) {
                // convert $offer['payment_method'] to a json array of payment methods
                $offer['payment_methods'] = explode(' ', $offer['payment_method']);
                // remove $offer['payment_method']
                unset($offer['payment_method']);
                if ($offer['type'] == 1) {
                    $buyOffers[$provider][] = $offer;
                } else {
                    $sellOffers[$provider][] = $offer;
                }
            }
        }

        return [
            'buyOffers' => $buyOffers,
            'sellOffers' => $sellOffers
        ];

    }

    public function getAllOffers($buyOffers, $sellOffers) {
        $allOffers = [];
        foreach ($buyOffers as $provider => $offers) {
            $allOffers[$provider] = $offers;
        }
        foreach ($sellOffers as $provider => $offers) {
            if (!array_key_exists($provider, $allOffers)) {
                $allOffers[$provider] = [];
            }
            $allOffers[$provider] = array_merge($allOffers[$provider], $offers);
        }

        return $allOffers;
    }

    public function getNegativePremiumBuyOffers($buyOffers, $minNegativePremium = -1) {

        // for buys the more negative the premium the better
        $negativePremiumBuyOffers = [];
        foreach ($buyOffers as $provider => $offers) {
            $negativePremiumBuyOffers[$provider] = [];
            foreach ($offers as $offer) {
                if ($offer['premium'] <= $minNegativePremium) {
                    $negativePremiumBuyOffers[$provider][] = $offer;
                }
            }
        }

        // remove giftcard payment methods
        // $negativePremiumBuyOffers = $this->removePaymentMethods($negativePremiumBuyOffers);

        // only accept revolut
        // $negativePremiumBuyOffers = $this->onlyPaymentMethods($negativePremiumBuyOffers, ['Revolut', 'Paypal Friends & Family']);

        return $negativePremiumBuyOffers;
    }

    public function getPositivePremiumSellOffers($sellOffers, $minPositivePremium = 0) {

        // for sells the more positive the premium the better
        $positivePremiumSellOffers = [];
        foreach ($sellOffers as $provider => $offers) {
            $positivePremiumSellOffers[$provider] = [];
            foreach ($offers as $offer) {
                if ($offer['premium'] >= $minPositivePremium) {
                    $positivePremiumSellOffers[$provider][] = $offer;
                }
            }
        }

        // remove giftcard payment methods
        // $positivePremiumSellOffers = $this->removePaymentMethods($positivePremiumSellOffers);

        // only accept revolut
        // $positivePremiumSellOffers = $this->onlyPaymentMethods($positivePremiumSellOffers, ['Revolut', 'Paypal, Friends, &, Family']);

        return $positivePremiumSellOffers;
    }

    public function removePaymentMethods($offers, $paymentMethods = []) {
        $filteredOffers = [];
        foreach ($offers as $provider => $providerOffers) {
            $filteredOffers[$provider] = [];
            foreach ($providerOffers as $offer) {
                // or if it contains the word giftcard
                if (strpos($offer['payment_methods'], 'Giftcard') === false) {
                    $filteredOffers[$provider][] = $offer;
                }
            }
        }

        return $filteredOffers;
    }

    public function onlyPaymentMethods($offers, $paymentMethods = []) {
        $filteredOffers = [];
        foreach ($offers as $provider => $providerOffers) {
            $filteredOffers[$provider] = [];
            foreach ($providerOffers as $offer) {
                foreach ($paymentMethods as $paymentMethod) {
                    if (in_array($paymentMethod, $offer['payment_methods'])) {
                        $filteredOffers[$provider][] = $offer;
                        break;
                    }
                }
            }
        }

        return $filteredOffers;
    }

    // public function claimCompensation($invoice, $provider, $privateKey) {
    //     // http://192.168.0.18:12596/mainnet/$provider/api/reward/
    //     // invoice: PGP SIGNED MESSAGE
    //
    //     // remove all /n from private key
    //     $privateKey = str_replace("\\n", '', $privateKey);
    //
    //     // at the end of "-----BEGIN PGP PRIVATE KEY BLOCK-----" add the \n back
    //     $privateKey = str_replace("-----BEGIN PGP PRIVATE KEY BLOCK-----", "-----BEGIN PGP PRIVATE KEY BLOCK-----\n", $privateKey);
    //
    //     $key->addSubKey($privateKey);
    //     // $gpg->addSignKey($privateKey);
    //     $signedInvoice = $gpg->sign($invoice);
    //     return $signedInvoice;
    //
    //     $url = 'http://192.168.0.18:12596/mainnet/' . $provider . '/api/reward/';




    //
    //
    // 1. take order
    // 2. store robot token istrKJlZqzDypRRG1uxSeCbpvS3zTLXQWfxp
    // 3. http://192.168.0.18:12596/mainnet/temple/api/order/?order_id=6984
    // 4. grab hold invoice from that
    // 5. status message "Waiting for taker bond"
    // 6. escrow invoice
    // 6. status message "Waiting for trade collateral and buyer invoice"
    //
    //
    //
    //
    // 1. POST http://192.168.0.18:12596/mainnet/temple/api/order/?order_id=6984

    public function acceptOffer($robosatsId) {
        $offer = Offer::where('robosatsId', $robosatsId)->first();

        // grab admin dashboard
        $adminDashboard = AdminDashboard::all()->first();
        $channelBalances = json_decode($adminDashboard->channelBalances, true);

        // grab the largest amount we can accept whether it is range or not
        $calculations = (new OfferController())->calculateLargestAmount($offer, $channelBalances);
        if (is_array($calculations) && $calculations['estimated_offer_amount'] > 0) {
            $offer->accepted_offer_amount_sat = $calculations['estimated_offer_amount_sats'];
            $offer->accepted_offer_amount = $calculations['estimated_offer_amount'];
            $offer->accepted_offer_profit_sat = $calculations['estimated_profit_sats'];
            // round satoshi to 0 decimal places
            $offer->accepted_offer_profit_sat = round($offer->accepted_offer_profit_sat, 0);
            $offer->accepted_offer_amount_sat = round($offer->accepted_offer_amount_sat, 0);
        } else {
            $slackService = new SlackService();
            $slackService->sendMessage('Error: Failed to accept offer: ' . $robosatsId . ' because the calculations failed');
            return 'Failed to accept offer: ' . $robosatsId . ' because the calculations failed';
        }
        $offer->accepted = true;


        // $transaction = new Transaction();
        if (Transaction::where('offer_id', $offer->id)->first()) {
            $transaction = Transaction::where('offer_id', $offer->id)->first();
        } else {
            $transaction = new Transaction();
        }
        $transaction->offer_id = $offer->id;


        // round to 0 decimal places
        // if ($offer->accepted_offer_profit_sat < 0) {
        //     (new SlackService)->sendMessage('Error: trying to accept offer with a negative profit');
        //     return 'Offer has a negative profit';
        // }
        // round to 0 decimal places
        if ($offer->accepted_offer_profit_sat < $adminDashboard->min_satoshi_profit) {
            (new SlackService)->sendMessage('Error: trying to accept offer with less than ' . $adminDashboard->min_satoshi_profit . ' sats profit');
            return 'Offer has less than ' . $adminDashboard->min_satoshi_profit . ' sats profit';
        }

        // // check if offer has the allowed payment methods
        $allowedPaymentMethods = json_decode($adminDashboard->payment_methods, true);
        $paymentMethods = json_decode($offer->payment_methods, true);
        $allowed = false;
        foreach ($paymentMethods as $paymentMethod) {
            if (in_array($paymentMethod, $allowedPaymentMethods)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            (new SlackService)->sendMessage('Error: Offer has no allowed payment methods');
            return 'Offer has no allowed payment methods';
        }
        // // check if offer has the allowed currency
        $allowedCurrencies = json_decode($adminDashboard->payment_currencies, true);
        if (!in_array($offer->currency, $allowedCurrencies)) {
            (new SlackService)->sendMessage('Error: Offer has no allowed currency');
            return 'Offer has no allowed currency';
        }

        // // check when the offer and btcFiat was last updated if too old, could suggest out of date prices
        $now = Carbon::now();
        // if the offer was last updated more than 10 minutes ago
        $offerUpdated = Carbon::parse($offer->updated_at);
        if ($now->diffInMinutes($offerUpdated) > 10) {
            (new SlackService)->sendMessage('Error: Offer is suspiciously old');
            return 'Offer is suspiciously old';
        }

        // if the btcFiat was last updated more than 10 minutes ago
        $btcFiat = BtcFiat::all()->first();
        $btcFiatUpdated = Carbon::parse($btcFiat->updated_at);
        if ($now->diffInMinutes($btcFiatUpdated) > 10) {
            (new SlackService)->sendMessage('Error: BtcFiat item is suspiciously old');
            return 'BtcFiat is suspiciously old';
        }


        // check if offer satoshi amount is above adminDashboard->max_satoshi_amount
        if ($offer->accepted_offer_amount_sat > $adminDashboard->max_satoshi_amount) {
            (new SlackService)->sendMessage('Error: Offer accepted amount is above max_satoshi_amount in admin dashboard');
            return 'Offer accepted amount is above max_satoshi_amount in admin dashboard';
        }


        // last chance to back out
        if ($adminDashboard->panicButton) {
            return 'Panic button is on';
        }

        // post request
        $url = $this->getHost() . '/mainnet/' . $offer->provider . '/api/order/?order_id=' . $robosatsId;
        // (new SlackService)->sendMessage($offer->accepted_offer_amount . ' ' . $offer->currency . '.  RoboSats ID: ' . $robosatsId);
        Log::info($offer->accepted_offer_amount . ' ' . $offer->currency . '.  RoboSats ID: ' . $robosatsId);

        if (!$offer->has_range) {
            $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->post($url, ['action' => 'take', 'amount' => $offer->accepted_offer_amount]);
        } else {
            $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->post($url, ['action' => 'take', 'amount' => $offer->accepted_offer_amount]);
        }
        if ($response == null || $response->failed()) {
            (new SlackService)->sendMessage('Failed to accept offer' . $response->body());
            // {"bad_request":"You are not a participant in this order"}
            if ($response->json('bad_request') == 'You are not a participant in this order') {
                $offer->accepted = false;
                $transaction->delete();
                $offer->delete();
            } else {
                (new SlackService)->sendMessage('For debugging here are the request parameters: Headers: ' . json_encode($this->getHeaders($offer)) . ' URL: ' . $url . ' Data: ' . json_encode(['action' => 'take', 'amount' => $offer->accepted_offer_amount]));
            }

            return 'Failed to accept offer';
        }
        $offer->save();

        (new SlackService)->sendMessage('Accepted offer: ' . round($offer->accepted_offer_amount,2) . ' ' . $offer->currency . ' for ' . $offer->accepted_offer_profit_sat . ' sats profit.');

        // convert response to json
        $response = json_decode($response->body(), true);

        if ($response['status_message']) {
            $transaction->status_message= $response['status_message'];
        }
        if ($response['bond_invoice']) {
            $transaction->bond_invoice = $response['bond_invoice'];
        }
        $transaction->save();

        return $transaction;
    }


    public function sendHandle($offer): void {

        $robot = $offer->robots()->first();
        $message = '';

        if (!$offer->my_offer) {
            // depending on what payment methods are available change the message, preference order is revolut, wise, paypal friends & family, strike
            $preferredPaymentMethods = PaymentMethod::where([['name', '!=', null], ['handle', '!=', null]])->get();
            // shuffle the preferred payment methods
            $preferredPaymentMethods->shuffle();
            foreach ($preferredPaymentMethods as $paymentMethod) {
                if (in_array($paymentMethod->name, json_decode($robot->offer->payment_methods))) {
                    $pseudonym = $paymentMethod->name;
                    $tag = $paymentMethod->handle;

                    $message = 'Hey! My ' . $pseudonym . ' is ' . $tag . ' - If possible, please put this number somewhere in the payment reference (' . $offer->id . '). '.
                        'This is just to help me to match your payment to your order, but is totally optional. Cheers!';
                    break;
                }
            }
        } else {

            // if it's our offer then we need to send all the payment methods that the buyer can use
            $paymentMethods = json_decode($robot->offer->payment_methods);

            // Fetching the available handles for the payment methods
            $handles = PaymentMethod::whereIn('name', $paymentMethods)->pluck('handle', 'name')->toArray();
            $handleParts = [];

            // Building the list of available handles
            foreach ($handles as $method => $handle) {
                if (in_array($method, $paymentMethods)) {
                    $handleParts[$method] = $handle;
                }
            }

            if (empty($handleParts)) {
                // If no payment methods match, we can return or handle the case accordingly
                $slackService = new SlackService();
                $slackService->sendMessage('Error: No matching payment methods found for the offer with ID ' . $offer->robosatsId);
            } else {
                // Building the final message
                $handleCount = count($handleParts);
                if ($handleCount == 1) {
                    // Singular case: "My Revolut is..."
                    $method = key($handleParts);
                    $message = "Hey! My $method is " . $handleParts[$method];
                } else {
                    // Plural case: "My handles are: Revolut: ... - Wise: ..."
                    $formattedHandles = [];
                    foreach ($handleParts as $method => $handle) {
                        $formattedHandles[] = "$method: $handle \n";
                    }
                    $message = "Hey! My handles are: \n\n" . implode("\n", $formattedHandles);
                }

                // Append the order ID reference
                $message .= "\nIf possible, please put this number somewhere in the payment reference (" . $offer->id . "). " .
                    "This is just to help me match your payment to your order, but is totally optional. Cheers!";

                if ($handleCount > 1) {
                    // Add a final note if there are multiple handles
                    $secondaryMessage = "Also kindly state which payment method you will be using. Thanks!";
                }
            }

        }

        $this->webSocketCommunicate($offer, $robot, $message);
        if (isset($secondaryMessage)) {
            sleep(5);
            $this->webSocketCommunicate($offer, $robot, $secondaryMessage);
        }

        (new SlackService)->sendMessage('Expect a payment for ' . round($robot->offer->accepted_offer_amount, 2) . ' ' . $robot->offer->currency
            . ' from one of these payment methods: ' . $robot->offer->payment_methods .
            ' soon! Once received, confirm the payment by typing !confirm ' . $offer->robosatsId . ' in the chat.', $offer->slack_channel_id);


    }

    public function webSocketCommunicate($offer, $robot, $messageContent): void {
        $b91 = new \Katoga\Allyourbase\Base91();
        $decoded = $b91->decode($robot->sha256);
        $hex = bin2hex($decoded);
        $url = $this->getWsHost() . '/mainnet/' . $offer->provider . '/ws/chat/' . $offer->robosatsId . '/?token_sha256_hex=' . $hex;

        // create a new client
        $client = new Client($url);
        $client
            // Add standard middlewares
            ->addMiddleware(new CloseHandler())
            ->addMiddleware(new PingResponder())
            ->onText(function (Client $client, Connection $connection, Message $message) use ($offer, $robot, $messageContent) {
                $peerPublicKey = json_decode($message->getContent(), true)['message'];

                $pgpService = new PgpService();
                $publicKey = $robot->public_key;
                $privateKey = $robot->private_key;


                // last chance to back out
                if (AdminDashboard::all()->first()->panicButton) {
                    return 'Panic button is on';
                }

                $encryptedMessage = $pgpService->encryptAndSign($publicKey, $privateKey, $messageContent , $robot->token, $peerPublicKey);
                $encryptedMessage = str_replace("\n", '\\', $encryptedMessage);


                $json = json_encode([
                    'type' => 'message',
                    'message' => $encryptedMessage,
                    'nick' => $robot->nickname
                ]);
                $client->text($json);

                // shutdown the client
                $client->close();

                return 'done';
            })
            ->start();
    }

    public function confirmReceipt(Offer $offer, Transaction $transaction) {
        $url = $this->getHost() . '/mainnet/' . $transaction->offer->provider . '/api/order/?order_id=' . $offer->robosatsId;

        // last chance to back out
        if (AdminDashboard::all()->first()->panicButton) {
            return 'Panic button is on';
        }

        // post request
        $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->post($url, ['action' => 'confirm']);

        $transaction->status_message = "Confirmed";
        $transaction->save();

        // convert response to json
        $response = json_decode($response->body(), true);

        $adminDashboard = AdminDashboard::all()->first();
//        $adminDashboard->trade_volume_satoshis += $transaction->offer->accepted_offer_amount_sat;
//        $adminDashboard->satoshi_profit += $transaction->offer->accepted_offer_profit_sat;



        // grab the lightning node
        $lightningNode = new LightningNode();
        // the fee is 0.025 if you are the maker of the offer and 0.175 if you are the taker
        $multiplier = 0.175;
        if ($transaction->offer->my_offer) {
            $multiplier = 0.025;
        }
        $multiplier = $multiplier / 100;

        $fees = $lightningNode->getPaymentFees($transaction->bond_invoice) + $lightningNode->getPaymentFees($transaction->escrow_invoice)
            + $transaction->offer->accepted_offer_amount_sat * $multiplier;
        $transaction->fees = $fees;
        $transaction->save();

//        $adminDashboard->satoshi_fees += $fees;


        $adminDashboard->save();
        (new SlackService)->sendMessage('Trade completed: ' .
            round($transaction->offer->accepted_offer_amount,2) . ' ' .
            $transaction->offer->currency . ' for ' .
            round($transaction->offer->accepted_offer_profit_sat,0) - $transaction->fees . ' sats profit.');

        return $response;
    }


    public function updateOfferStatus($offer): Offer
    {
        $url = $this->getHost() . '/mainnet/' . $offer->provider . '/api/order/?order_id=' . $offer->robosatsId;
        $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->get($url);
        $response = json_decode($response->body(), true);

        if (!$response || $response == null) {
            return $response;
        }


        $offer = (new OfferController())->insertOffer($response, $offer->provider);
        $offer->job_last_status = null;
        $offer->save();

        // create a transaction as we have the bond invoice in the response
        // check if the transaction exists

        if (array_key_exists('bond_invoice', $response)) {
            $transaction = $offer->transaction()->first();
            if ($transaction == null) {
                $transaction = new Transaction();
                $transaction->offer_id = $offer->id;
            }
            $transaction->bond_invoice = $response['bond_invoice'];
            $transaction->save();
        }

        return $offer;
    }

    // update status of transaction
    public function updateTransactionStatus($offer) {
        // update the offer as well
        // $this->updateOfferStatus($offer);

        $transaction = $offer->transaction()->first();
        $url = $this->getHost() . '/mainnet/' . $transaction->offer->provider . '/api/order/?order_id=' . $offer->robosatsId;

        try {
            $response = Http::withHeaders($this->getHeaders($offer))->timeout(20)->get($url);
        } catch (\Exception $e) {
            //!TODO we need some error handling here
            return null;
        }

        $response = json_decode($response->body(), true);
        if (isset($response['bad_request']) ) {
            if ($offer->status < 14) {
                $offer->status_message = $response['bad_request'];
                $offer->status = 99;
                // set expires at to now
                $offer->expires_at = date('Y-m-d H:i:s');
                $offer->save();
                $transaction->status_message = $response['bad_request'];
                $transaction->status = 99;
                $transaction->save();
            } else {
                $slackService = new SlackService();
                $slackService->sendMessage('Error on an offer that is already completed: ' . $response['bad_request'] . ' - ' . $offer->robosatsId . ' - ' .
                    ' - This is likely due to the offer not being retired yet, but the transaction is still being checked.');
            }
            return $response;
        }

        if (!$response || $response == null) {
            return $response;
        }
        if (isset($response['status'])) {
            $offer->status = $response['status'];
        }
        // if status is > 3 then we can add accepted_offer_amount_sat and accepted_offer_amount_profit_sat
        if (isset($response['status']) && $response['status'] > 3) {
            $offer->accepted_offer_amount = $response['amount'];
            $offer->accepted_offer_amount_sat = $response['satoshis_now'];
            $offer->accepted_offer_profit_sat = round($response['satoshis_now'] * ($response['premium'] / 100), 0);
        }
        if (isset($response['status_message'])) {
            $offer->status_message = $response['status_message'];
        }
        $offer->expires_at = date('Y-m-d H:i:s', strtotime($response['expires_at']));
        $offer->created_at = date('Y-m-d H:i:s', strtotime($response['created_at']));
        $offer->maker = $response['maker'];
        $offer->taker = $response['taker'];
        $offer->escrow_duration = $response['escrow_duration'];
        $offer->total_secs_exp = $response['total_secs_exp'];
        $offer->is_maker = $response['is_maker'];
        $offer->is_taker = $response['is_taker'];
        $offer->is_participant = $response['is_participant'];
        $offer->maker_nick = $response['maker_nick'];
        $offer->maker_hash_id = $response['maker_hash_id'];
        $offer->satoshis_now = $response['satoshis_now'];
        if (isset($response['maker_status'])) {
            $offer->maker_status = $response['maker_status'];
        }
        if (isset($response['taker_status'])) {
            $offer->taker_status = $response['taker_status'];
            $offer->is_buyer = $response['is_buyer'];
            $offer->is_seller = $response['is_seller'];
            $offer->is_fiat_sent = $response['is_fiat_sent'];
            $offer->is_disputed = $response['is_disputed'];
            $offer->taker_locked = $response['taker_locked'];
            $offer->escrow_locked = $response['escrow_locked'];
            $offer->ur_nick = $response['ur_nick'];
            $offer->maker_locked = $response['maker_locked'];
        }
        if (isset($response['trade_satoshis'])) {
            $offer->trade_satoshis = $response['trade_satoshis'];
        }
        if (isset($response['asked_for_cancel'])) {
            $offer->asked_for_cancel = $response['asked_for_cancel'];
        }
        if (isset($response['pending_cancel'])) {
            // if this was false and is now true then we need to send a message to slack
            if (!$offer->pending_cancel && $response['pending_cancel']) {
                $slackService = new SlackService();
                $slackService->sendMessage('*Collaborative cancel initiated by counterparty for order ' . $offer->robosatsId . '*', $offer->slack_channel_id);
            }
            $offer->pending_cancel = $response['pending_cancel'];
        }
        if (isset($response['chat_last_index'])) {
            $offer->chat_last_index = $response['chat_last_index'];
        }
        $offer->save();
        $offer->fixProfitSigns();

        if (isset($response['escrow_invoice'])) {
            $transaction->escrow_invoice = $response['escrow_invoice'];
        }
        if (isset($response['bond_invoice'])) {
            $transaction->bond_invoice = $response['bond_invoice'];
        }
        if (isset($response['status_message'])) {
            $transaction->status = $response['status'];
            $transaction->status_message = $response['status_message'];
        } else {
            // log response {"id":10213,"status":1,"created_at":"2024-07-03T21:16:35.391831Z","expires_at":"2024-07-04T21:15:35.391831Z","type":0,"currency":2,"amount":"200.00000000","has_range":false,"min_amount":null,"max_amount":null,"payment_method":"Wise","is_explicit":false,"premium":"2.80","satoshis":null,"maker":59843,"taker":null,"escrow_duration":10800,"bond_size":"3.00","latitude":null,"longitude":null,"total_secs_exp":86340,"penalty":"2024-07-03T23:19:29.668012Z","is_maker":false,"is_taker":false,"is_participant":false,"maker_nick":"CourteousAmount532","maker_hash_id":"3c2fced4b96d01fba681da4cc6b64c6891efc93e871286810dae507fa7265450","maker_status":"Inactive","price_now":57449,"premium_now":2.8,"satoshis_now":348134}
            Log::info('Unusual response from Robosats: ' . json_encode($response));
            $transaction->status_message= 'Unknown';
        }
        if ($offer->status != 1) {
            $transaction->save();
        }

        return $response;
    }

    public function updateRobot(Robot $robot) {
        $offer = $robot->offer;
        $url = $this->getHost() . '/mainnet/' . $robot->provider . '/api/robot/';
        // post request
        $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->get($url);
        $response = json_decode($response->body(), true);

        if (!isset($response['found'])) {
            Log::error($response);

            // $robot->save();
            return $response;
        }
        $robot->earned_rewards = $response['earned_rewards'];
        $robot->last_login = $response['last_login'];
        $robot->public_key_latter = $response['public_key'];
        $robot->private_key_latter = $response['encrypted_private_key'];
        // convert to date
        $robot->last_login = date('Y-m-d H:i:s', strtotime($robot->last_login));
        // $robot->last_order_id = $response['last_order_id'];
        $robot->save();
        return $response;

    }

    public function claimCompensation($robot) {
        $url = $this->getHost() . '/mainnet/' . $robot->provider . '/api/reward/';

        $earnedRewards = $robot->earned_rewards;
        $lightningNode = new LightningNode();
        $invoice = $lightningNode->createInvoice($earnedRewards, 'Claiming compensation for robot ' . $robot->id);
        // sign invoice
        $pgpService = new PgpService();
        $signedInvoice = $pgpService->sign($robot->private_key, $invoice, $robot->token, $robot->public_key);

        // post request
        $response = Http::withHeaders($this->getHeaders($robot->offer))->timeout(90)->post($url, ['invoice' => $signedInvoice]);
        $response = json_decode($response->body(), true);

        // send slack message
        $slackService = new SlackService();
        $message = "*Claiming compensation for robot* " . $robot->token . " with " . $earnedRewards . " sats.  Response: " . json_encode($response);
        $slackService->sendMessage($message);

        return $response;
    }

    public function collaborativeCancel($offer)
    {
        $url = $this->getHost() . '/mainnet/' . $offer->provider . '/api/order/?order_id=' . $offer->robosatsId;
        $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->post($url, ['action' => 'cancel']);
        $response = json_decode($response->body(), true);

        $slackService = new SlackService();
        $slackService->sendMessage('Collaborative cancel initiated for order ' . $offer->robosatsId, $offer->slack_channel_id);

        return $response;
    }

    public function createOffer(
        $type,
        $currency,
        $premium,
        $provider,
        $minAmount,
        $paymentMethods,
        $bondSize = 3,
        $ttl = 7200,
        $escrowDuration = 28800,
        $latitute = null,
        $longitude = null,
        $templateSlug = null,
        $maxAmount = null,
    ) {
        $isRange = $maxAmount != null;

        $slackService = new SlackService();
        $message = 'Creating ' . $type . ' offer on ' . $provider . ' for ';
        if ($isRange) {
            $message .= 'between ' . $minAmount . ' and ' . $maxAmount . ' ' . $currency . ' with a premium of ' . $premium . '%';
        } else {
            $message .= $minAmount . ' ' . $currency . ' with a premium of ' . $premium . '%';
        }
        $slackService->sendMessage($message);


        // create temp  offer, create robots, create offer, pay bond.
        $tempOffer = new Offer([
            'robosatsId' => rand(111111111, 999999999),
            'provider' => $provider,
            'type' => $type,
            'currency' => 0,
            'amount' => 0,
            'has_range' => $isRange,
            'payment_methods' => $paymentMethods,
            'is_explicit' => false,
            'premium' => $premium,
            'escrow_duration' => 0,
            'bond_size' => 0,
            'latitude' => $latitute,
            'longitude' => $longitude,
            'maker_nick' => '',
            'maker_hash_id' => '',
            'satoshis_now' => 0,
            'price' => 0,
            'maker_status' => '',
            'expires_at' => Carbon::now()->addMinutes(5),
            'max_satoshi_amount_profit' => 0,
            'satoshi_amount_profit' => 0,
            'accepted' => false,
            'maker' => 0,
            'posted_offer_template_slug' => $templateSlug,
            'my_offer' => true
        ]);

        $tempOffer->save();

        try {
            $robots = $this->createRobots($tempOffer);
        } catch (Exception $e) {
            $tempOffer->delete();
            $slackService->sendMessage('Failed to create sell offer.  Error: ' . $e->getMessage());
            return $e->getMessage();
        }

        // convert currency to int
        $currency = $this->currencyToInt($currency);

        $paymentMethods = implode(' ', json_decode($paymentMethods));
        $url = $this->getHost() . '/mainnet/' . $provider . '/api/make/';
        // round lat and long to 5 decimal places
        $latitute = round($latitute, 4);
        $longitude = round($longitude, 5);

        $typeInt = 0;
        if ($type == 'sell') {
            $typeInt = 1;
        } else if ($type == 'buy') {
            $typeInt = 0;
        }

        $array = [
            'type' => $typeInt,
            'currency' => $currency,
            'has_range' => $isRange,
            'max_amount' => $maxAmount,
            'payment_method' => $paymentMethods,
            'is_explicit' => false,
            'premium' => $premium,
            'satoshis' => null,
            'public_duration' => $ttl,
            'escrow_duration' => $escrowDuration,
            'bond_size' => $bondSize,
            'latitude' => $latitute,
            'longitude' => $longitude
        ];

        // if latitude and longitude == 0 then remove them
        if ($latitute == 0 && $longitude == 0) {
            $array['latitude'] = null;
            $array['longitude'] = null;
        }


        // add min amount if it is a range
        if ($isRange) {
            $array['min_amount'] = $minAmount;
            $array['amount'] = null;
            $array['satoshis'] = null;
        } else {
            $array['amount'] = $minAmount;
        }

        try {
            $response = Http::withHeaders($this->getHeaders($tempOffer))->timeout(30)->post($url, $array);
        } catch (\Exception $e) {
            // its okay to delete the temp offer and the robots here as the bond has not been paid
            $tempOffer->delete();
            foreach ($robots as $robot) {
                $robot->delete();
            }
            $slackService->sendMessage('Failed to create sell offer.  Error: ' . $e->getMessage());
            return $e->getMessage();
        }
        $response = json_decode($response->body(), true);

        // if the response is null or failed
        if ($response == null || !array_key_exists('id', $response)) {
            // delete the temp offer
            $tempOffer->delete();
            // delete the robots
            foreach ($robots as $robot) {
                $robot->delete();
            }
            $slackService->sendMessage('Failed to create sell offer.  Error: ' . json_encode($response));
            return $response;
        }

        // update robosatsId
        $tempOffer->robosatsId = $response['id'];
        $tempOffer->expires_at = date('Y-m-d H:i:s', strtotime($response['expires_at']));
        $tempOffer->created_at = date('Y-m-d H:i:s', strtotime($response['created_at']));
        $tempOffer->maker = $response['maker'];
        $tempOffer->save();

        $offer = $this->updateOfferStatus($tempOffer);
        $offer->my_offer = true;
        $offer->save();





        return $response;

    }

    private function currencyToInt($currency)
    {
        // change the likes of GBP to 2 using CURRENCIES
        $currency = strtoupper($currency);
        foreach ($this::CURRENCIES as $key => $value) {
            if ($value == $currency) {
                return $key;
            }
        }
        return null;
    }

//    http://robodexarjwtfryec556cjdz3dfa7u47saek6lkftnkgshvgg2kcumqd.onion

    // private function requestWithTor($url, $headers = [], $timeout = 30) {
    //     $tor = new Tor();
    //     $tor->setTorAddress('
    //

    public function updateInvoice($offer, $routingBudgetPpm = 1000, $exactAmount = null) {
        if ($offer->type == "sell") {
            $slackService = new SlackService();
            $slackService->sendMessage('Error: Cannot update invoice for sell offer');
        }

        $url = $this->getHost() . '/mainnet/' . $offer->provider . '/api/order/?order_id=' . $offer->robosatsId;
        if ($exactAmount) {
            $computedInvoiceAmount = $exactAmount;
        } else {
            $computedInvoiceAmount = $this->computeInvoiceAmount($offer->accepted_offer_amount_sat, $routingBudgetPpm);
        }
        $lightningNode = new LightningNode();
        $invoice = $lightningNode->createInvoice($computedInvoiceAmount, 'Invoice for ' . $offer->id);
        $pgpService = new PgpService();
        $robot = $offer->robots()->first();
        $signedInvoice = $pgpService->sign(
            $robot->private_key,
            $invoice,
            $robot->token,
            $robot->public_key
        );

        $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->post($url, [
            'action' => 'update_invoice',
            'invoice' => $signedInvoice,
            'routing_budget_ppm' => $routingBudgetPpm
        ]);
        // this is some pretty garbage code
        if ($response->json('bad_invoice') && $exactAmount == null) {
            // remove any none numeric characters
            $correctedAmount = preg_replace('/[^0-9]/', '', $response->json('bad_invoice'));
            $slackService = new SlackService();
            $slackService->sendMessage('Invoice updated for ' . $offer->robosatsId, $offer->slack_channel_id);
            return $this->updateInvoice($offer, $routingBudgetPpm, $correctedAmount);
        }

        return json_decode($response->body(), true);
    }


    public function computeInvoiceAmount(int $sats, int $ppm = 1000): int {
        return floor($sats - ($sats * ($ppm / 1000000)));
    }
}
