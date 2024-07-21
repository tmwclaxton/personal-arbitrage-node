<?php

namespace App\Services;

use Brick\Math\BigDecimal;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use OTPHP\TOTP;

class KrakenService
{

    private \Butschster\Kraken\Client $client;

    public function __construct()
    {
        $this->client = new \Butschster\Kraken\Client(
            new Client(),
            new \Butschster\Kraken\NonceGenerator(),
            (new \Butschster\Kraken\Serializer\SerializerFactory())->build(),
            env('KRAKEN_API_KEY'),
            env('KRAKEN_PRIVATE_KEY')
        );
    }

    public function getClient(): \Butschster\Kraken\Client
    {
        return $this->client;
    }

    public function getGBPBalance(): BigDecimal
    {
        $response = $this->client->getAccountBalance();
        return $response['ZGBP']->getBalance();
    }

    public function getBTCBalance(): BigDecimal
    {
        $response = $this->client->getAccountBalance();
        return $response['XXBT']->getBalance();
    }


    // convert gbp amt to btc
    public function convertGBPToBTC($amtInGBP): BigDecimal
    {
        $httpResponse = Http::get('https://mempool.space/api/v1/prices');
        $btcPrice = $httpResponse->json()['GBP'];

        $btcAmt = $amtInGBP / $btcPrice;
        // convert btcAmt to big decimal
        return BigDecimal::of($btcAmt);
    }

    public function buyFullAmt(): \Butschster\Kraken\Responses\Entities\AddOrder\OrderAdded|\Illuminate\Http\JsonResponse
    {
        $response = $this->getGBPBalance();
        $floatAmt = round($response->toFloat(), 2, PHP_ROUND_HALF_DOWN);
        return $this->buyBitcoin($floatAmt);
    }

    public function buyBitcoin($amtInGBP): \Butschster\Kraken\Responses\Entities\AddOrder\OrderAdded|\Illuminate\Http\JsonResponse
    {
        // if less than £6 return error as it is not enough to buy bitcoin
        if ($amtInGBP < 6) {
            return response()->json(['error' => 'Amount is too low to buy bitcoin'], 400);
        }


        // buy bitcoin
        $order = new \Butschster\Kraken\Requests\AddOrderRequest(
            new \Butschster\Kraken\ValueObjects\OrderType('market'),
            new \Butschster\Kraken\ValueObjects\OrderDirection('buy'),
            'XXBTZGBP',
        );
        $order->setVolume($this->convertGBPToBTC($amtInGBP));
        return $this->client->addOrder($order);
    }


    public function sendBtcToLightning($btcAmt): \Illuminate\Http\JsonResponse
    {
        // $response = $this->client->withdraw('XXBT', $btcAmt, 'lightning');
        // return response()->json($response);
    }

    public function getOTP(): string
    {
        $otp = TOTP::createFromSecret(env("KRAKEN_OTP_KEY"));
        return $otp->now();
    }

}
