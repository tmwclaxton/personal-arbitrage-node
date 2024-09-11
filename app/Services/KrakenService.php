<?php

namespace App\Services;

use App\WorkerClasses\LightningNode;
use Brick\Math\BigDecimal;
use Facebook\WebDriver\WebDriverBy;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OTPHP\TOTP;

class KrakenService
{

    private \Butschster\Kraken\Client $client;
    private string $apiUrl = "https://api.kraken.com";

    private const API_VERSION = 0;

    private Client $httpClient;

    private SlackService $slackService;

    public function __construct()
    {
        $adminDashboard = \App\Models\AdminDashboard::all()->first();
        $this->client = new \Butschster\Kraken\Client(
            new Client(),
            new \Butschster\Kraken\NonceGenerator(),
            (new \Butschster\Kraken\Serializer\SerializerFactory())->build(),
            $adminDashboard->kraken_api_key,
            $adminDashboard->kraken_private_key
        );
        $this->httpClient = new Client();
        $this->discordService = new SlackService();
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

    public function getEURBalance(): BigDecimal
    {
        $response = $this->client->getAccountBalance();
        return $response['ZEUR']->getBalance();
    }

    public function getUSDBalance(): BigDecimal
    {
        $response = $this->client->getAccountBalance();
        return $response['ZUSD']->getBalance();
    }

    public function getBTCBalance(): BigDecimal
    {
        $response = $this->client->getAccountBalance();
        return $response['XXBT']->getBalance();
    }


    // convert gbp amt to btc
    public function convertCurrencyToBTC($currency, $amtInGBP): BigDecimal
    {
        $httpResponse = Http::get('https://mempool.space/api/v1/prices');
        $btcPrice = $httpResponse->json()[$currency];

        $btcAmt = $amtInGBP / $btcPrice;
        // convert btcAmt to big decimal
        return BigDecimal::of($btcAmt);
    }


    public function buyFullAmt($currency, $amount): \Butschster\Kraken\Responses\Entities\AddOrder\OrderAdded|\Illuminate\Http\JsonResponse
    {
        // subtract 2 from the amount to account for fees  given its big decimal
        $amount = $amount->minus(2);
        // dd($response);
        $floatAmt = round($amount->toFloat(), 2, PHP_ROUND_HALF_DOWN);
        return $this->buyBitcoin($floatAmt, $currency);
    }

    public function sendFullAmtToLightning() {


        $slackService = new SlackService();
        $slackService->sendMessage('Sending BTC to lightning node');

        $krakenService = new \App\Services\KrakenService();
        $btcBalance = $krakenService->getBTCBalance();
        // $this->discordService->sendMessage('Sending ' . $btcBalance . ' BTC to lightning node');
        // make btc balance a big decimal
        $btc = $btcBalance->jsonSerialize();
        // ensure satoshis is an integer
        $satoshis = intval($btc * 100000000) - 2000; // possible fees?
        $lightningNode = new LightningNode();
        $invoice = $lightningNode->createInvoice($satoshis, 'Kraken BTC Withdrawal of ' . $btcBalance . ' BTC at ' . Carbon::now()->toDateTimeString());
        // $slackService->sendMessage('Invoice created: ' . $invoice);


        // THIS IS TO SET REDIS KEYS plz dont delete
        $gmailService = new GmailService();
        $code = $gmailService->getLinkFromLastEmail();

        $invoiceId = "ag_lightning_invoice_" . Carbon::now()->toDateTimeString();


        # hit suave container
        $response = Http::post('http://suave:' . env('SUAVE_PORT') .
            '/kraken-withdraw', [
            'lightning_invoice' => $invoice,
            'invoice_id' => $invoiceId
        ]);


        sleep(5);

        $krakenService->withdrawFunds(
            'XBT',
            $invoiceId,
            $btc
        );


        $slackService = new SlackService();
        $slackService->sendMessage('Withdrawal Complete: ' . $btc . ' BTC');
        // to ' . $invoice);

        return response()->json([
            'success' => 'Withdrawal request sent to discord',
            'invoice' => $invoice,
        ]);
    }


    public function buyBitcoin($amount, $currency): \Butschster\Kraken\Responses\Entities\AddOrder\OrderAdded|\Illuminate\Http\JsonResponse
    {
        // if less than £6 return error as it is not enough to buy bitcoin
        if ($amount < 6) {
            return response()->json(['error' => 'Amount is too low to buy bitcoin'], 400);
        }

        $this->discordService->sendMessage('Buying bitcoin with ' . $amount . ' ' . $currency);

        $pairs = [
            'GBP' => 'XXBTZGBP',
            'EUR' => 'XXBTZEUR',
            'USD' => 'XXBTZUSD',
        ];
        $selectedPair = $pairs[$currency];

        // buy bitcoin
        $order = new \Butschster\Kraken\Requests\AddOrderRequest(
            new \Butschster\Kraken\ValueObjects\OrderType('market'),
            new \Butschster\Kraken\ValueObjects\OrderDirection('buy'),
            $selectedPair
        );
        $order->setVolume($this->convertCurrencyToBTC($currency, $amount));
        $orderCreation = $this->client->addOrder($order);


        $btcPurchase = new \App\Models\BtcPurchase();
        $btcPurchase->tx_id = $orderCreation->txId[0];
        $btcPurchase->primaryDescription = $orderCreation->description->order;
        $btcPurchase->save();

        return $orderCreation;
    }


    // public function sendBtcToLightning($btcAmt): \Illuminate\Http\JsonResponse
    // {
    //     // $response = $this->client->withdraw('XXBT', $btcAmt, 'lightning');
    //     // return response()->json($response);
    // }

    public function getOTP(): string
    {
        $adminDashboard = \App\Models\AdminDashboard::all()->first();
        $otp = TOTP::createFromSecret($adminDashboard->kraken_totp_key);
        return $otp->now();
    }


    public function request(
        string $method,
        array $parameters = [],
        string $requestMethod = 'POST',
    ) {
        $headers = ['User-Agent' => 'Kraken PHP API Agent'];
        $isPublic = Str::startsWith($method, 'public/');

        if (!$isPublic) {
            // $parameters['otp'] = $this->getOTP();
            $nonce = explode(' ', microtime());
            $nonce = $nonce[1] . str_pad(substr($nonce[0], 2, 6), 6, '0');
            $parameters['nonce'] = $nonce;
            $headers['API-Key'] = env('KRAKEN_API_KEY');
            $headers['API-Sign'] = $this->makeSignature($method, $parameters);
        }

        $response = match ($requestMethod) {
            'GET' => $this->httpClient->request($requestMethod, $this->apiUrl . $this->buildPath($method), [
                'headers' => $headers,
                'query' => $parameters,
                'verify' => true,
            ]),
            default => $this->httpClient->request($requestMethod, $this->apiUrl . $this->buildPath($method), [
                'headers' => $headers,
                'form_params' => $parameters,
                'verify' => true,
            ]),
        };

        return $response->getBody()->getContents();


    }


    private function buildPath(string $method): string
    {
        return '/' . self::API_VERSION . '/' . $method;
    }

    /**
     * Message signature using HMAC-SHA512 of (URI path + SHA256(nonce + POST data))
     * and base64 decoded secret API key
     */
    private function makeSignature(string $method, array $parameters = []): string
    {
        $queryString = http_build_query($parameters, '', '&');
        $adminDashboard = \App\Models\AdminDashboard::all()->first();

        $signature = hash_hmac(
            'sha512',
            $this->buildPath($method) . hash('sha256', $parameters['nonce'] . $queryString, true),
            base64_decode($adminDashboard->kraken_private_key),
            true
        );

        return base64_encode($signature);
    }

    public function withdrawFunds(string $asset, string $key, string $amount)
    {
        return $this->request(
            method: 'private/Withdraw',
            parameters: [
                'asset' => $asset,
                'key' => $key,
                'amount' => $amount,
            ],
        );
    }

}
