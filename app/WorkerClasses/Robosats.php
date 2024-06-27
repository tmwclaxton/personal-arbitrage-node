<?php

namespace App\WorkerClasses;

use Illuminate\Support\Facades\Http;

class Robosats
{

    // list of different providers [satstralia, temple, lake, veneto]
    public $providers = [
        'satstralia' => 'mainnet/satstralia/',
        'temple' => 'mainnet/temple/',
        'lake' => 'mainnet/lake/',
        'veneto' => 'mainnet/veneto/',
        'exp' => 'mainnet/exp/'
    ];

    public function request($endpoint) {
        $providers = $this->providers;
        $urlStart = 'http://192.168.0.18:12596/';
        // create urls $urlStart + $providers[index] + $endpoint
        $responses = [];
        foreach ($providers as $provider) {
            $url = $urlStart . $provider . $endpoint;
            $headers = [
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

            try {
                $response = Http::withHeaders($headers)->timeout(15)->get($url);
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
                if ($offer['premium'] < $minNegativePremium) {
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
                if ($offer['premium'] > $minPositivePremium) {
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
                if (!in_array($offer['payment_method'], $paymentMethods) && !str_contains(strtolower($offer['payment_method']), 'giftcard')) {
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
                if (in_array($offer['payment_method'], $paymentMethods)) {
                    $filteredOffers[$provider][] = $offer;
                }
            }
        }

        return $filteredOffers;
    }

}
