<?php

namespace App\WorkerClasses;

use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Models\Robot;
use App\Models\Transaction;
use App\Services\PgpService;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

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
    protected string $host = 'http://192.168.0.18:12596';
    protected string $wsHost = 'ws://192.168.0.18:12596';

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
                "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0",
                "Accept" => "*/*",
                "Accept-Language" => "en-GB,en;q=0.5",
                "Accept-Encoding" => "gzip, deflate",
                "Referer" => "http://192.168.0.18:12596/",
                "Content-Type" => "application/json",
                "Connection" => "keep-alive",
                "Priority" => "u=4",
                "Pragma" => "no-cache",
                "Cache-Control" => "no-cache",
                "Origin" => "http://192.168.0.18:12596",


    ];

    public function getHeaders($offer = null)
    {
        if ($offer) {
            $tokenSha256 = $offer->robots()->first()->sha256;
            $tokenSha256 = str_replace("\n", '', $tokenSha256);
            $tokenSha256 = str_replace("\r", '', $tokenSha256);
            $this->headers["Authorization"] = "Token " . $tokenSha256;
            $this->headers["Priority"] = "u=1";
            // remove new lines and \r
        }
        $adminDash = AdminDashboard::all()->first();
        $this->headers["Cookie"] = "UMBREL_PROXY_TOKEN=" . $adminDash->umbrel_token;
        // dd($this->headers);
        return $this->headers;
    }



    public function createRobot($offer) {
        // check if the offer already has a robot
        $robots = Robot::where('offer_id', $offer->id)->get();
        if ($robots->count() > 0) {
            return $robots;
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

        $privateKeyUnescaped = $keyPair['private_key'];
        $publicKeyUnescaped = $keyPair['public_key'];
        // replace \n with \
        $privateKey = str_replace("\n", '\\', $privateKeyUnescaped);
        $publicKey = str_replace("\n", '\\', $publicKeyUnescaped);

        // ideal format for authentication
        $authentication = 'Token ' . $b91Token . ' | Public ' . $publicKey . ' | Private ' . $privateKey;
        // remove new lines and \r
        $authentication = str_replace("\n", '', $authentication);
        $authentication = str_replace("\r", '', $authentication);
        // at the end of ----- add \\
        // $authentication = str_replace('-----BEGIN PGP PUBLIC KEY BLOCK-----', '-----BEGIN PGP PUBLIC KEY BLOCK-----\\\\', $authentication);
        // $authentication = str_replace('-----END PGP PUBLIC KEY BLOCK-----', '\\-----END PGP PUBLIC KEY BLOCK-----\\', $authentication);
        // $authentication = str_replace('-----BEGIN PGP PRIVATE KEY BLOCK-----', '-----BEGIN PGP PRIVATE KEY BLOCK-----\\\\', $authentication);
        // $authentication = str_replace('-----END PGP PRIVATE KEY BLOCK-----', '\\-----END PGP PRIVATE KEY BLOCK-----\\\\', $authentication);


        foreach ($this->providers as $provider) {
            $url = $this->host . '/mainnet/' . $provider . '/api/robot/';
            $headers = $this->getHeaders();
            $headers['Authorization'] = $authentication;
            $headers['Referer'] = $this->host . '/robot/';
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
                return $e->getMessage();
                // continue;
            }

            $json = json_decode($response->body(), true);
            if (empty($json) || $json == null) {
                continue;
            }

            $robot = new Robot();
            $robot->provider = $provider;
            $robot->offer_id = $offer->id;
            $robot->token = $generatedToken;
            $robot->sha256 = $b91Token;
            $robot->nickname = $json['nickname'];
            $robot->hash_id = $json['hash_id'];
            $robot->public_key = $publicKey;
            $robot->private_key = $privateKey;
            $robot->encrypted_private_key = $json['encrypted_private_key'];
            $robot->earned_rewards = $json['earned_rewards'];
            $robot->wants_stealth = $json['wants_stealth'];
            // convert last_login to datetime from 2024-06-28T23:39:02.732620Z to 2024-06-28 23:39:02
            $robot->last_login = date('Y-m-d H:i:s', strtotime($json['last_login']));
            $robot->tg_enabled = $json['tg_enabled'];
            $robot->tg_token = $json['tg_token'];
            $robot->tg_bot_name = $json['tg_bot_name'];
            $robot->save();
        }

        return $robots;
    }


    public function request($endpoint, $offer = null) {
        $providers = $this->providers;
        $urlStart = 'http://192.168.0.18:12596/mainnet/';
        $responses = [];
        foreach ($providers as $provider) {
            $url = $urlStart . $provider . '/' . $endpoint;

            try {
                $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->get($url);
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
        // $prices = $this->request('api/limits/');
        // satstralia i.e. {"1":{"code":"USD","price":61571.7,"min_amount":12.31434,"max_amount":3078.585},"2":{"code":"EUR","price":57521.67,"min_amount":11.504334,"max_amount":2876.0835},"3":{"code":"JPY","price":9900374.23,"min_amount":1980.0748460000002,"max_amount":495018.71150000003},"4":{"code":"GBP","price":48803.225,"min_amount":9.760645,"max_amount":2440.16125},"5":{"code":"AUD","price":92637.64,"min_amount":18.527528,"max_amount":4631.8820000000005},"6":{"code":"CAD","price":84411.53,"min_amount":16.882306,"max_amount":4220.5765},"7":{"code":"CHF","price":55340.55,"min_amount":11.06811,"max_amount":2767.0275},"8":{"code":"CNY","price":447518.5,"min_amount":89.50370000000001,"max_amount":22375.925000000003},"9":{"code":"HKD","price":480611.6,"min_amount":96.12232,"max_amount":24030.58},"10":{"code":"NZD","price":101250.84,"min_amount":20.250168,"max_amount":5062.542},"11":{"code":"SEK","price":654196.17,"min_amount":130.839234,"max_amount":32709.808500000003},"12":{"code":"KRW","price":86149048.21,"min_amount":17229.809642,"max_amount":4307452.4105},"13":{"code":"SGD","price":83678.95,"min_amount":16.73579,"max_amount":4183.9475},"14":{"code":"NOK","price":657280.61,"min_amount":131.456122,"max_amount":32864.0305},"15":{"code":"MXN","price":1135994.98,"min_amount":227.198996,"max_amount":56799.749},"16":{"code":"BYN","price":201556.27,"min_amount":40.311254,"max_amount":10077.8135},"17":{"code":"RUB","price":5388862.595,"min_amount":1077.772519,"max_amount":269443.12975},"18":{"code":"ZAR","price":1155902.21,"min_amount":231.180442,"max_amount":57795.1105},"19":{"code":"TRY","price":2025260.455,"min_amount":405.052091,"max_amount":101263.02275},"20":{"code":"BRL","price":339447.905,"min_amount":67.889581,"max_amount":16972.39525},"21":{"code":"CLP","price":58803286.58,"min_amount":11760.657316,"max_amount":2940164.329},"22":{"code":"CZK","price":1443060.46,"min_amount":288.612092,"max_amount":72153.023},"23":{"code":"DKK","price":429016.205,"min_amount":85.80324100000001,"max_amount":21450.810250000002},"24":{"code":"HRK","price":284053.66,"min_amount":56.810731999999994,"max_amount":14202.682999999999},"25":{"code":"HUF","price":22805511.815,"min_amount":4561.102363000001,"max_amount":1140275.5907500002},"26":{"code":"INR","price":5138760.865,"min_amount":1027.752173,"max_amount":256938.04325000002},"27":{"code":"ISK","price":8307038.255,"min_amount":1661.407651,"max_amount":415351.91275},"28":{"code":"PLN","price":248444.33,"min_amount":49.688866,"max_amount":12422.2165},"29":{"code":"RON","price":256528.16,"min_amount":51.305632,"max_amount":12826.408000000001},"30":{"code":"ARS","price":82433519.37,"min_amount":16486.703874000003,"max_amount":4121675.9685000004},"31":{"code":"VES","price":2494300.98,"min_amount":498.86019600000003,"max_amount":124715.049},"32":{"code":"COP","price":252627040.73,"min_amount":50525.408146,"max_amount":12631352.0365},"33":{"code":"PEN","price":237091.73,"min_amount":47.41834600000001,"max_amount":11854.586500000001},"34":{"code":"UYU","price":2427094.32,"min_amount":485.418864,"max_amount":121354.716},"35":{"code":"PYG","price":464184702.52,"min_amount":92836.940504,"max_amount":23209235.126000002},"36":{"code":"BOB","price":425558.59,"min_amount":85.11171800000001,"max_amount":21277.929500000002},"37":{"code":"IDR","price":1017010115.03,"min_amount":203402.023006,"max_amount":50850505.7515},"38":{"code":"ANG","price":111000.07,"min_amount":22.200014000000003,"max_amount":5550.003500000001},"39":{"code":"CRC","price":32199268.12,"min_amount":6439.853624,"max_amount":1609963.4060000002},"40":{"code":"CUP","price":22162374.24,"min_amount":4432.474848,"max_amount":1108118.712},"41":{"code":"DOP","price":3640463.74,"min_amount":728.092748,"max_amount":182023.18700000003},"42":{"code":"GHS","price":939054.76,"min_amount":187.81095200000001,"max_amount":46952.738000000005},"43":{"code":"GTQ","price":478574.37,"min_amount":95.71487400000001,"max_amount":23928.718500000003},"44":{"code":"ILS","price":231047.74,"min_amount":46.209548,"max_amount":11552.387},"45":{"code":"JMD","price":9607738.04,"min_amount":1921.5476079999999,"max_amount":480386.902},"46":{"code":"KES","price":7958687.67,"min_amount":1591.737534,"max_amount":397934.3835},"47":{"code":"KZT","price":28734233.24,"min_amount":5746.846648,"max_amount":1436711.662},"48":{"code":"MYR","price":291233.85,"min_amount":58.24677,"max_amount":14561.6925},"49":{"code":"NAD","price":1130222.79,"min_amount":226.04455800000002,"max_amount":56511.139500000005},"50":{"code":"NGN","price":92085360.48,"min_amount":18417.072096000004,"max_amount":4604268.024},"51":{"code":"AZN","price":104655.66,"min_amount":20.931132,"max_amount":5232.783},"52":{"code":"PAB","price":61562.15,"min_amount":12.31243,"max_amount":3078.1075},"53":{"code":"PHP","price":3627217.97,"min_amount":725.4435940000001,"max_amount":181360.8985},"54":{"code":"PKR","price":17145247.09,"min_amount":3429.049418,"max_amount":857262.3545},"55":{"code":"QAR","price":224642.87,"min_amount":44.928574000000005,"max_amount":11232.1435},"56":{"code":"SAR","price":230964.88,"min_amount":46.192976,"max_amount":11548.244},"57":{"code":"THB","price":2268107.825,"min_amount":453.62156500000003,"max_amount":113405.39125000002},"58":{"code":"TTD","price":418487.19,"min_amount":83.697438,"max_amount":20924.359500000002},"59":{"code":"VND","price":1637682541.35,"min_amount":327536.50827,"max_amount":81884127.0675},"60":{"code":"XOF","price":37721768.14,"min_amount":7544.353628000001,"max_amount":1886088.4070000001},"61":{"code":"TWD","price":2008134.79,"min_amount":401.626958,"max_amount":100406.73950000001},"62":{"code":"TZS","price":161777267.95,"min_amount":32355.453589999997,"max_amount":8088863.3975},"63":{"code":"XAF","price":37721768.14,"min_amount":7544.353628000001,"max_amount":1886088.4070000001},"64":{"code":"UAH","price":2836622.42,"min_amount":567.324484,"max_amount":141831.121},"65":{"code":"EGP","price":2956842.41,"min_amount":591.3684820000001,"max_amount":147842.12050000002},"66":{"code":"LKR","price":18841459.67,"min_amount":3768.2919340000008,"max_amount":942072.9835000001},"67":{"code":"MAD","price":611875.51,"min_amount":122.37510200000001,"max_amount":30593.775500000003},"68":{"code":"AED","price":226117.78,"min_amount":45.223556,"max_amount":11305.889000000001},"69":{"code":"TND","price":193106.25,"min_amount":38.62125,"max_amount":9655.3125},"70":{"code":"ETB","price":6110473.59,"min_amount":1222.094718,"max_amount":305523.6795},"71":{"code":"GEL","price":172374.02,"min_amount":34.474804,"max_amount":8618.701},"72":{"code":"UGX","price":228490343.15,"min_amount":45698.06863,"max_amount":11424517.1575},"73":{"code":"RSD","price":6732128.98,"min_amount":1346.4257960000002,"max_amount":336606.449},"74":{"code":"IRT","price":3782994157.69,"min_amount":756598.831538,"max_amount":189149707.88450003},"75":{"code":"BDT","price":7235805.26,"min_amount":1447.161052,"max_amount":361790.26300000004},"76":{"code":"ALL","price":5765679.74,"min_amount":1153.135948,"max_amount":288283.987},"300":{"code":"XAU","price":26.45,"min_amount":0.00529,"max_amount":1.3225},"1000":{"code":"BTC","price":1.0,"min_amount":0.0002,"max_amount":0.05}}
        $provider = array_rand($this->providers);
        $url = $this->host . '/mainnet/' . $this->providers[$provider] . '/api/limits/';

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
        $negativePremiumBuyOffers = $this->onlyPaymentMethods($negativePremiumBuyOffers, ['Revolut', 'Paypal Friends & Family']);

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
        // grab local balance
        $localBalance = $adminDashboard->localBalance;
        // grab the offer price amount or max amount
        if ($offer->has_range) {
            $amount = $offer->max_amount;
        } else {
            $amount = $offer->amount;
        }
        if ($localBalance < $amount + 40000) {
            return 'Insufficient balance (ps need 40000 extra for fees for bond and potentially fees)';
        }

        $offer->accepted = true;
        $offer->save();
        $transaction = new Transaction();
        $transaction->offer_id = $offer->id;
        // $transaction->save();

        $url = $this->host . '/mainnet/' . $offer->provider . '/api/order/?order_id=' . $robosatsId;
        // post request
        if (!$offer->has_range) {
            $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->post($url, ['action' => 'take', 'amount' => $offer->amount]);
        } else {
            $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->post($url, ['action' => 'take', 'amount' => $offer->max_amount]);
            $offer->amount = $offer->max_amount;
            $offer->save();
        }
        if ($response == null || $response->failed()) {
            $transaction->delete();
            return 'Failed to accept offer';
        }

        // convert response to json
        $response = json_decode($response->body(), true);

        if ($response['status_message']) {
            $transaction->status = $response['status_message'];
        }
        if ($response['bond_invoice']) {
            $transaction->bond_invoice = $response['bond_invoice'];
        }
        $transaction->save();

        return $transaction;
    }


    public function webSocketCommunicate($offer) { ;

        $robot = $offer->robots()->first();

        $b91 = new \Katoga\Allyourbase\Base91();
        $decoded = $b91->decode($robot->sha256);
        $hex = bin2hex($decoded);
        $url = $this->wsHost . '/mainnet/' . $offer->provider . '/ws/chat/' . $offer->robosatsId . '/?token_sha256_hex=' . $hex;

        // create a new client
        $client = new Client($url);
        $client
            // Add standard middlewares
            ->addMiddleware(new CloseHandler())
            ->addMiddleware(new PingResponder());

        $receivedMessages = [];

        $publicKey = $robot->publicKey;
        // replace \\ with \n
        $publicKey = str_replace("\\", "\n", $publicKey);
        // send the first message being the pgp public key
        // Send a message
        $client->text(json_encode(['type' => 'message', 'message' => $publicKey, 'nick' => $robot->nickname]));
        // Read response (this is blocking)
        $message = $client->receive();
        $receivedMessages[] = $message->getContent();
        dd( "Got message: {$message->getContent()}" );

        // Send an encrypted message "Hey there, my revolut is @vidgazeltd, please leave the note empty!  Cheers! "

        $adminDashboard = AdminDashboard::all()->first();
        $revtag = $adminDashboard->revolut_handle;

        $privateKey = $robot->private_key;
        // replace \\ with \n
        $privateKey = str_replace("\\", "\n", $privateKey);

        $pgpService = new PgpService();
        $encryptedMessage = $pgpService->encryptAndSign($privateKey, 'I have received payment' , $robot->token);
        $encryptedMessage = str_replace("\n", '\\', $encryptedMessage);


        $json = json_encode([
            'type' => 'message',
            'message' => $encryptedMessage,
            'nick' => $robot->nickname
        ]);
        $client->text($json);

        // Read response (this is blocking)
        $message = $client->receive();
        $receivedMessages[] = $message->getContent();
        echo "Got message: {$message->getContent()} \n";

        $client->close();

        return $receivedMessages;


        // {"type":"message","message":"-----BEGIN PGP PUBLIC KEY BLOCK-----\n\nmQENBGaAcyUBCACgzbe9xq3RAyaOAp6gS1pEuqIvTK5MZjH9054lwqqxk0RtEP5n\ntdxLUIRZWwSv8K6bwP2rdh3arM4kXpb886JPSXvj5f75xq5zmy6G85OpVHhhhtxq\nT0nl8+vVI4qIPoPynAjAqbtKmLlw1bj57Oato7bj95i1thGS9FB1DWCI+6Yrneat\nU0W0PY0/gwcwYjjjIhosJmqPLhbDqNmoUmU+rq5sRbcxGpVXqB6InX9T4ic0BtQY\nDg7+/BRzaqW5Tr0TcU3NFeEVfL4A7WgdkAEF9lWhdyFGjEf2B2MURwtWxm85xc3J\n9MOLA7FLmE25rxE6VmeDuRDQ2tnFSrtNALDJABEBAAG0TVJvYm9TYXRzIElEOiAx\nMTYyZDdlY2I3NTBkZmFlMDExY2ZhMTI1ZmVjYmU3NmY2NGZiOTA0MTViM2UxODE1\nZDQ1YmM0YTkwN2RiNDZmiQErBBMBCAAf/wAAAAUCZoBzJf8AAAACGwf/AAAACRBe\nBW9sDssYgQAAW4cH/1uHaTY68+YDYH69ajzzyiDAak+SDLAisNXgx8/Cd1jBKYfG\n0Mwlv7C4KN+etmCP2S7jnR9IOsbbGWvhKk45fQyAvozvp7LEabT+Y8ieMUUA1aj0\nK1Ny2vciNr3Eo2qqYZp26bZx6dOO46v41B9HFQzOoc/NLCTooTS4fom4ihvfw8nE\n0CdhIo6eeiX07ATEMEd53wclQ/xZeh5jkWTdc9wBbaATYIToQqoLi/IEzwUnmnTJ\noZElItWKJD1QaDuXYIfNX0xs7FaNzi0rZbd9hg7FHRLIJYFgZUWyG9dzPA2dnxzx\niTjtPH65m1LlyHWTfu1wHt3DS6e351YRtHM5Q8Q=\n=mrc6\n-----END PGP PUBLIC KEY BLOCK-----\n","nick":"UnfilledGrenade349"}

        // {"type":"message","message":"-----SERVE HISTORY-----","nick":"UnfilledGrenade349"}



        // first message we send is our pgp public key for that provider
        // i,e
        //    {
        // 	"type": "message",
        // 	"message": "-----BEGIN PGP PUBLIC KEY BLOCK-----\n\nmDMEZnsichYJKwYBBAHaRw8BAQdANovtfPCgwEeg3iauWeqDvcvhMzcV8RdFwclW\nPaZO6v20TFJvYm9TYXRzIElEIDBkNTYyNTgwZTM2NGM0ZDY5MTM2ZDMxOWYzZTFj\nYTRkZGRjYzExODZhNGQxMTc3MTA3N2RhYjdlYmI1YzFlMmSIjAQQFgoAPgWCZnsi\ncgQLCQcICZAxiNYePu5GBgMVCAoEFgACAQIZAQKbAwIeARYhBHcp6lmY/wgi/8k6\n9TGI1h4+7kYGAAAGGQEArRmXz1cDuJq0D5TgNXk7wvkKeYfYw69+BnpK/eH9/jQB\nANx3Uu0ZWDlhnejwkzFl0374IpcHk8pVc8/2jEO5WIkHuDgEZnsichIKKwYBBAGX\nVQEFAQEHQIiek/u9KJf7MjKvHdWUuBm+F2OG8cwJNIVt7BMCSw4pAwEIB4h4BBgW\nCgAqBYJmeyJyCZAxiNYePu5GBgKbDBYhBHcp6lmY/wgi/8k69TGI1h4+7kYGAABC\nfAEA1v+L22xPnl6hMP66QE0FzXRQFmFHs5O83yQkI3dtc24BAKJZdxMYKhoAc8pE\nvYNFPHYUz+Oefs+88ca5c3gzQW4J\n=sM0E\n-----END PGP PUBLIC KEY BLOCK-----\n",
        // 	"nick": "TatteredSurgery892"
        // }

        // second message we send is our pgp encrypted message after 2 minutes
        // Hey there, my revolut is @vidgazeltd, please leave the note empty!  Cheers!


        // {"type":"message",
        //"message":"-----BEGIN PGP MESSAGE-----\\\\wV4DR+PDKn7dATISAQdAixJBHOIE2lkuGXRAxhbySYNZpHY0EPU6cSnL7xTU\\1XcwSNJiN8IflBUJyqPOELxPQKfWJQE8P6Hl6lih85jUSFcJIh7OlnkUxwTv\\SixLTlNcwV4DK+P+Ln2+h9YSAQdAvFyc9mrvj5HvIh7dEgzLbpQbJ1AgBFj/\\uP9nzCRlW0QwRh3QSBq7YmFYcddq9h4ApvbjNni3me5hxpQ+ygtT/k/dgvi4\\vcape9B6eo5axdd+0sAcAV8w5AiCTCz0/guhCYebwSfU9KsmjTVDWyH9qsv8\\VoaihkfHsPqnfGp1Fypy0mpY0/SBo3ViBllriX4xjI2fdm6CYmnIjAZORjO2\\BjFeqsmFALPc7pe4trDEqHs4gB/+aInmQVO156mAqpuf+gM96mNw+Mydwfl8\\boa4Rz0TriiNHTya9IqeXTdB4FC6O7mnNi99+xTKAjRWEc0V5MHUNq4GfuAL\\/FOsYKt+itArWUwEq3qbqEZVncW5m3CztcF+Box+tQNVDUw4EUaqQmqdf6+r\\Iz+Uc+6NeDdWnw==\\=laPu\\-----END PGP MESSAGE-----\\",
        //"nick":"CurativeConic344"}
    }

    public function confirmReceipt(Offer $offer, Transaction $transaction) {
        $url = $this->host . '/mainnet/' . $transaction->offer->provider . '/api/order/?order_id=' . $offer->robosatsId;
        // post request
        $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->post($url, ['action' => 'confirm']);

        $transaction->status = "Confirmed";
        $transaction->save();

        // convert response to json
        $response = json_decode($response->body(), true);

        $adminDashboard = AdminDashboard::all()->first();
        $adminDashboard->trade_volume_satoshis += $transaction->offer->satoshis_now;
        $adminDashboard->satoshi_profit += $transaction->offer->satoshi_amount_profit;
        $adminDashboard->save();


        return $response;
    }

    // update status of transaction
    public function updateTransactionStatus($offer) {
        $transaction = $offer->transaction()->first();
        $url = $this->host . '/mainnet/' . $transaction->offer->provider . '/api/order/?order_id=' . $offer->robosatsId;

        $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->get($url);
        $response = json_decode($response->body(), true);

        if (isset($response['bad_request'])) {
            $offer->status_message = $response['bad_request'];
            $offer->save();
            $transaction->status = $response['bad_request'];
            $transaction->save();
            return $response;
        }

        if (!$response || $response == null) {
            return $response;
        }
        if (isset($response['status'])) {
            $offer->status = $response['status'];
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
        $offer->maker_status = $response['maker_status'];
        $offer->taker_status = $response['taker_status'];
        $offer->is_buyer = $response['is_buyer'];
        $offer->is_seller = $response['is_seller'];
        $offer->is_fiat_sent = $response['is_fiat_sent'];
        $offer->is_disputed = $response['is_disputed'];
        $offer->ur_nick = $response['ur_nick'];
        $offer->satoshis_now = $response['satoshis_now'];
        $offer->maker_locked = $response['maker_locked'];
        $offer->taker_locked = $response['taker_locked'];
        $offer->escrow_locked = $response['escrow_locked'];
        if (isset($response['trade_satoshis'])) {
            $offer->trade_satoshis = $response['trade_satoshis'];
        }
        if (isset($response['asked_for_cancel'])) {
            $offer->asked_for_cancel = $response['asked_for_cancel'];
        }
        if (isset($response['chat_last_index'])) {
            $offer->chat_last_index = $response['chat_last_index'];
        }
        $offer->save();


        if (isset($response['escrow_invoice'])) {
            $transaction->escrow_invoice = $response['escrow_invoice'];
        }
        if (isset($response['bond_invoice'])) {
            $transaction->bond_invoice = $response['bond_invoice'];
        }
        if (isset($response['status_message'])) {

            $transaction->status = $response['status_message'];
        } else {
            // log response
            Log::info('Unknown response from robosats: ' . json_encode($response));

            $transaction->status = 'Unknown';
        }
        $transaction->save();

        return $response;
    }

    public function updateRobot(Robot $robot) {
        $offer = $robot->offer;
        $url = $this->host . '/mainnet/' . $robot->provider . '/api/robot/';
        // post request
        $response = Http::withHeaders($this->getHeaders($offer))->timeout(30)->get($url);
        $response = json_decode($response->body(), true);

        if (!$response['found']) {
            $robot->save();
            return $response;
        }
        $robot->earned_rewards = $response['earned_rewards'];
        $robot->last_login = $response['last_login'];
        // convert to date
        $robot->last_login = date('Y-m-d H:i:s', strtotime($robot->last_login));
        // $robot->last_order_id = $response['last_order_id'];
        $robot->save();
        return $response;

    }

    public function claimCompensation($robot) {
        $url = $this->host . '/mainnet/' . $robot->provider . '/api/reward/';

        $earnedRewards = $robot->earned_rewards;
        $lightningNode = new LightningNode();
        $invoice = $lightningNode->createInvoice($earnedRewards, 'Claiming compensation for robot ' . $robot->id);
        // sign invoice
        $pgpService = new PgpService();
        $signedInvoice = $pgpService->sign($invoice, $robot->private_key);

        // post request
        $response = Http::withHeaders($this->getHeaders($robot->offer))->timeout(30)->post($url, ['invoice' => $signedInvoice]);
        $response = json_decode($response->body(), true);

        //     // http://192.168.0.18:12596/mainnet/$provider/api/reward/
        //     // invoice: PGP SIGNED MESSAGE
        // $signed = $crypt_gpg->sign('hello world', Crypt_GPG::SIGN_MODE_CLEAR);
        //




        return $response;
    }



    //
    // {"id":6984,"status":3,"created_at":"2024-06-27T06:25:07.984166Z","expires_at":"2024-06-28T00:34:13.731564Z","type":0,"currency":2,"amount":"100.00000000","has_range":false,"min_amount":null,"max_amount":null,"payment_method":"Revolut","is_explicit":false,"premium":"2.00","satoshis":null,"maker":71794,"taker":71680,"escrow_duration":10800,"bond_size":"3.00","latitude":null,"longitude":null,"total_secs_exp":200,"is_maker":false,"is_taker":true,"is_participant":true,"maker_nick":"StatusPlace548","maker_hash_id":"37855626811cf2097c7aa2547bf69bd03726a25d6ddd98fb6bbfa58b0f389b0c","maker_status":"Inactive","taker_status":"Active","price_now":58566.0,"premium_now":2.0,"satoshis_now":172915,"is_buyer":false,"is_seller":true,"taker_nick":"IdealisticBlur631","taker_hash_id":"8f5e582dcc4b69d236681f834c10cc2db5e4640fdb75a4335901d54b300514c3","status_message":"Waiting for taker bond","is_fiat_sent":false,"is_disputed":false,"ur_nick":"IdealisticBlur631","maker_locked":true,"taker_locked":false,"escrow_locked":false,"bond_invoice":"lnbc51220n1pn8uppapp5nwlmr9sua5h6x8f75dgunmwpckx2fx2uredkk2du3k4cejan0dcqd2j2pshjmt9de6zqun9vejhyetwvdjn5gr9893nye3kx9jz6vnyx3sj6dpcvdnz6c34vycz6ephvycnzwpnxdnrjvp59cs9g6rfwvs8qcted4jkuapq2ay5cnpqgefy2326g5syjn3qt984253q2aq5cnz92skzqcmgv43kkgr0dcs9ymmzdafkzarnyp5kvgr5dpjjqmr0vd4jqampwvs8xatrvdjhxumxw4kzugzfwss8w6tvdssxyefqw4hxcmmrddjkggpgveskjmpfyp6kumr9wdejq7t0w5sxx6r9v96zqmmjyp3kzmnrv4kzqatwd9kxzar9wfskcmre9ccqz2cxqzfvsp5u5tda8gk0lxu9eeefvzkq6je6m43n4cr8tkkmg26wjc8mf6zukcq9qxpqysgquplmw2dcdk8a93pgms80kzt4d8cvuqvnyr40jhk7cprkn7kz8j9j6m9x4zg8wjlgpxnj6wqa96ymckmjtwcz8qq24g2zc8agzt423qsp39ngx6","bond_satoshis":5122}
    //
    // 2. until GET request http://192.168.0.18:12596/mainnet/temple/api/order/?order_id=6984
    //
    // status message != Waiting for taker bond
    //
    //                               3. once status message = 'Waiting for trade collateral and buyer invoice' grab the escrow_invoice
    //
    // {"id":6984,"status":6,"created_at":"2024-06-27T06:25:07.984166Z","expires_at":"2024-06-28T03:32:32.974165Z","type":0,"currency":2,"amount":"100.00000000","has_range":false,"min_amount":null,"max_amount":null,"payment_method":"Revolut","is_explicit":false,"premium":"2.00","satoshis":null,"maker":71794,"taker":71680,"escrow_duration":10800,"bond_size":"3.00","latitude":null,"longitude":null,"total_secs_exp":10800,"is_maker":false,"is_taker":true,"is_participant":true,"maker_nick":"StatusPlace548","maker_hash_id":"37855626811cf2097c7aa2547bf69bd03726a25d6ddd98fb6bbfa58b0f389b0c","maker_status":"Inactive","taker_status":"Active","is_buyer":false,"is_seller":true,"taker_nick":"IdealisticBlur631","taker_hash_id":"8f5e582dcc4b69d236681f834c10cc2db5e4640fdb75a4335901d54b300514c3","status_message":"Waiting for trade collateral and buyer invoice","is_fiat_sent":false,"is_disputed":false,"ur_nick":"IdealisticBlur631","satoshis_now":170767,"maker_locked":true,"taker_locked":true,"escrow_locked":false,"trade_satoshis":171066,"escrow_invoice":"lnbc1710660n1pn8up9ppp5x9d8sdrupt2y2escs2y7l0aty8e6qndxsl50fngkpm27t00398uqd2j2pshjmt9de6zqun9vejhyetwvdjn5gr9893nye3kx9jz6vnyx3sj6dpcvdnz6c34vycz6ephvycnzwpnxdnrjvp59cs9g6rfwvs8qcted4jkuapq2ay5cnpqgefy2326g5syjn3qt984253q2aq5cnz92skzqcmgv43kkgr0dcs9ymmzdafkzarnyp5kvgr5dpjjqmr0vd4jqampwvs8xatrvdjhxumxw4kzugzfwss8w6tvdssxyefqw4hxcmmrddjkggpgveskjmpfyp6kumr9wdejq7t0w5sxx6r9v96zqmmjyp3kzmnrv4kzqatwd9kxzar9wfskcmre9ccqz2cxqr06gsp5fux2slru6cezavzzd5s35q9v4drpe4dmv2ma4qt7aaejezgx353q9qxpqysgqaeuar0efhhzgglskl8wcq7j3vhzdrwde20dyvu8pggc54kuxqnf9dmp3sym3r2wcstdnsav99sz50dyd9hjh78pga58a3cdvy7wfskcqzf6ucj","escrow_satoshis":171066}
    //
    // 4. once that's paid status message = 'Waiting only for buyer invoice'
    //
    //

    // 5. IF THEY DON'T PAY IN TIME
    // {"id":6984,"status":5,"created_at":"2024-06-27T06:25:07.984166Z","expires_at":"2024-06-28T03:32:32.974165Z","type":0,"currency":2,"amount":"100.00000000","has_range":false,"min_amount":null,"max_amount":null,"payment_method":"Revolut","is_explicit":false,"premium":"2.00","satoshis":null,"maker":71794,"taker":71680,"escrow_duration":10800,"bond_size":"3.00","latitude":null,"longitude":null,"total_secs_exp":0,"is_maker":false,"is_taker":true,"is_participant":true,"maker_nick":"StatusPlace548","maker_hash_id":"37855626811cf2097c7aa2547bf69bd03726a25d6ddd98fb6bbfa58b0f389b0c","maker_status":"Inactive","taker_status":"Active","price_now":58645.0,"premium_now":2.0,"satoshis_now":170767,"is_buyer":false,"is_seller":true,"taker_nick":"IdealisticBlur631","taker_hash_id":"8f5e582dcc4b69d236681f834c10cc2db5e4640fdb75a4335901d54b300514c3","status_message":"Expired","is_fiat_sent":false,"is_disputed":false,"ur_nick":"IdealisticBlur631","maker_locked":false,"taker_locked":false,"escrow_locked":false,"public_duration":86340,"expiry_reason":3,"expiry_message":"Invoice not submitted"}



}
