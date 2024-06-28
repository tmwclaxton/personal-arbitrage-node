<?php

namespace App\WorkerClasses;

use App\Models\Offer;
use Crypt_GPG;
use Crypt_GPG_Key;
use Illuminate\Support\Facades\Http;

class Robosats
{

    // list of different providers [satstralia, temple, lake, veneto]
    public array $providers = [
        'satstralia' => 'mainnet/satstralia/',
        'temple' => 'mainnet/temple/',
        'lake' => 'mainnet/lake/',
        'veneto' => 'mainnet/veneto/',
        'exp' => 'mainnet/exp/'
    ];

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

    protected string $host = 'http://192.168.0.18:12596';

    protected array $headers = [
                "Host" => "192.168.0.18:12596",
                "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0",
                "Accept" => "*/*",
                "Accept-Language" => "en-GB,en;q=0.5",
                "Accept-Encoding" => "gzip, deflate",
                "Referer" => "http://192.168.0.18:12596/",
                "Content-Type" => "application/json",
                "Connection" => "keep-alive",
                "Cookie" => "UMBREL_PROXY_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwcm94eVRva2VuIjp0cnVlLCJpYXQiOjE3MTk0MzI5MzQsImV4cCI6MTcyMDAzNzczNH0.31qKPyd1zRoySVRPVzisbTxO_FljIisBOHJFyJs6JYc",
                "Priority" => "u=4",
                "Pragma" => "no-cache",
                "Cache-Control" => "no-cache"
            ];


    public function request($endpoint) {
        $providers = $this->providers;
        $urlStart = 'http://192.168.0.18:12596/';
        $responses = [];
        foreach ($providers as $provider) {
            $url = $urlStart . $provider . $endpoint;

            try {
                $response = Http::withHeaders($this->headers)->timeout(30)->get($url);
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

        // $url = 'http://192.168.0.18:12596/mainnet/satstralia/api/limits/';

        // pick random provider
        $provider = array_rand($this->providers);
        $url = $this->host . '/' . $this->providers[$provider] . 'api/limits/';

        $response = Http::withHeaders($this->headers)->timeout(30)->get($url);
        // convert response to json
        $prices = json_decode($response->body(), true);
        // filter for USD, EUR, GBP
        // $filteredPrices = [];
        // foreach ($prices as $price) {
        //     if (in_array($price['code'], ['USD', 'EUR', 'GBP'])) {
        //         $filteredPrices[] = $price;
        //     }
        // }

        // return $filteredPrices;

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
        $negativePremiumBuyOffers = $this->onlyPaymentMethods($negativePremiumBuyOffers, ['Revolut']);

        return $negativePremiumBuyOffers;
    }

    public function getPositivePremiumSellOffers($sellOffers, $minPositivePremium = 1) {

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
        $positivePremiumSellOffers = $this->onlyPaymentMethods($positivePremiumSellOffers, ['Revolut']);

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
    //
    //
    // }



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

    public function acceptOffer($offerId) {
        $offer = Offer::find($offerId);
        $url = $this->host . '/mainnet/' . $offer->provider . '/api/order/?order_id=' . $offerId;
        // post request
        $response = Http::withHeaders($this->headers)->timeout(30)->post($url);
        // convert response to json
        $response = json_decode($response->body(), true);



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
