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

    private DiscordService $discordService;

    public function __construct()
    {
        $this->client = new \Butschster\Kraken\Client(
            new Client(),
            new \Butschster\Kraken\NonceGenerator(),
            (new \Butschster\Kraken\Serializer\SerializerFactory())->build(),
            env('KRAKEN_API_KEY'),
            env('KRAKEN_PRIVATE_KEY')
        );
        $this->httpClient = new Client();
        $this->discordService = new DiscordService();
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
        // subtract 2 from the amount to account for fees  given its big decimal
        $response = $response->minus(2);
        // dd($response);
        $floatAmt = round($response->toFloat(), 2, PHP_ROUND_HALF_DOWN);
        return $this->buyBitcoin($floatAmt);
    }

    public function sendFullAmtToLightning() {



        $krakenService = new \App\Services\KrakenService();
        $btcBalance = $krakenService->getBTCBalance();
        // $this->discordService->sendMessage('Sending ' . $btcBalance . ' BTC to lightning node');
        // make btc balance a big decimal
        $btc = $btcBalance->jsonSerialize();
        // ensure satoshis is an integer
        $satoshis = intval($btc * 100000000);
        $lightningNode = new LightningNode();
        $invoice = $lightningNode->createInvoice($satoshis, 'Kraken BTC Withdrawal of ' . $btcBalance . ' BTC at ' . Carbon::now()->toDateTimeString());


        $seleniumService = new \App\Services\SeleniumService();
        $driver = $seleniumService->getDriver();
        // THIS IS TO SET REDIS KEYS plz dont delete
        $code = $seleniumService->getLinkFromLastEmail('https://www.kraken.com/withdrawal-approve?code=');
        $code = $seleniumService->getLinkFromLastEmail();
        //

        // sign in // possibly with otp
        $seleniumService->signin($krakenService);

        // approve device
        $seleniumService->approveDevice();

        // set session key lightning-network-shown-in-current-session to true
        $driver->executeScript("window.localStorage.setItem('lightning-network-shown-in-current-session', 'true')");

        // sign in again
        $seleniumService->signin($krakenService, 'https://www.kraken.com/sign-in?redirect=%2Fc%2Ffunding%2Fwithdraw%3Fasset%3DBTC%26assetType%3Dcrypto%26network%3DLightning%26method%3DBitcoin%2520Lightning');
        // sleep(rand(1,2));
        // $links = $seleniumService->getLinks();
        // $seleniumService->clickLinksWithText($links, ["Transfer crypto"]);
        // sleep(rand(1,2));
        // $buttons = $seleniumService->getButtons();
        // $seleniumService->clickButtonsWithText($buttons[0], $buttons[1], ["Withdraw"]);
        //
        // // select button by id network-selector
        // $driver->findElement(WebDriverBy::id("network-selector"))->click();
        //
        // sleep(rand(1,2));
        //
        // // select list item by id downshift-0-item-1
        // $driver->findElement(WebDriverBy::id("downshift-0-item-1"))->click();

        sleep(rand(10, 15));

        list($buttons, $buttonValues) = $seleniumService->getButtons();

        $seleniumService->clickButtonsWithText($buttons, $buttonValues, ["Okay", "Agree and continue"]);

        // scroll down slightly
        $driver->executeScript("window.scrollTo(0," . rand(650, 720) . ")");

        sleep(5);


        list($buttons, $buttonValues) = $seleniumService->getButtons();
        $counts = 0;
        // $counts += $seleniumService->clickButtonsWithText($buttons, $buttonValues, ["Manage withdrawal requests"]);
        // $counts += $seleniumService->clickButtonsWithText($buttons, $buttonValues, ["Manage requests"]);

        // if clicks === 0 then click button with class TextButton_root__fIpnJ

        // if ($counts === 0) {
        // $driver->findElement(WebDriverBy::className(".TextButton_root__fIpnJ"))->click();
        // document.querySelector('.TextButton_root__fIpnJ').click()

        // run script to click button
        $driver->executeScript("document.querySelector('.TextButton_root__fIpnJ').click()");

        sleep(5);

        list($buttons, $buttonValues) = $seleniumService->getButtons();
        $seleniumService->clickButtonsWithText($buttons, $buttonValues, ["Add withdrawal request"]);

        try {
            // find an input with id label and send keys to it
            $driver->findElement(WebDriverBy::id("label"))->click();
            $invoiceId = "ag_lightning_invoice_" . Carbon::now()->toDateTimeString();
            $driver->findElement(WebDriverBy::id("label"))->sendKeys($invoiceId);
            $driver->findElement(WebDriverBy::id("address"))->click();
            $driver->findElement(WebDriverBy::id("address"))->sendKeys($invoice);
        } catch (\Exception $e) {
            $driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
            $source = $driver->getPageSource();
            $driver->quit();

            $discordService = new DiscordService();
            $discordService->sendMessage('Error sending invoice for Kraken BTC withdrawal');
            dd($source, $e, $buttons, $buttonValues);

        }

        sleep(2);

        list($buttons, $buttonValues) = $seleniumService->getButtons();
        $seleniumService->clickButtonsWithText($buttons, $buttonValues, ["Add withdrawal request"]);


        // grab email
        $iterations = 0;
        $code = null;
        while ($code === null) {
            sleep(5);
            $code = $seleniumService->getLinkFromLastEmail('https://www.kraken.com/withdrawal-approve?code=');
            $iterations++;
            if ($iterations > 5) {
                $discordService = new DiscordService();
                $discordService->sendMessage('No link found in most recent email from Kraken.');
                return response()->json(['error' => 'No link found in most recent email from Kraken.']);
            }
        }

        $driver->get($code);

        sleep(5);

        $krakenService->withdrawFunds(
            'XBT',
            $invoiceId,
            $btc
        );

        // Close the driver
        $driver->quit();

        $discordService = new DiscordService();
        $discordService->sendMessage('Withdrawal Complete: ' . $btc . ' BTC to ' . $invoice);

        return response()->json([
            'success' => 'Withdrawal request sent',
            'invoice' => $invoice,
        ]);
    }


    public function buyBitcoin($amtInGBP): \Butschster\Kraken\Responses\Entities\AddOrder\OrderAdded|\Illuminate\Http\JsonResponse
    {
        // if less than £6 return error as it is not enough to buy bitcoin
        if ($amtInGBP < 6) {
            return response()->json(['error' => 'Amount is too low to buy bitcoin'], 400);
        }

        $this->discordService->sendMessage('Buying bitcoin with ' . $amtInGBP . ' GBP');

        // buy bitcoin
        $order = new \Butschster\Kraken\Requests\AddOrderRequest(
            new \Butschster\Kraken\ValueObjects\OrderType('market'),
            new \Butschster\Kraken\ValueObjects\OrderDirection('buy'),
            'XXBTZGBP',
        );
        $order->setVolume($this->convertGBPToBTC($amtInGBP));
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
        $otp = TOTP::createFromSecret(env("KRAKEN_OTP_KEY"));
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

        $signature = hash_hmac(
            'sha512',
            $this->buildPath($method) . hash('sha256', $parameters['nonce'] . $queryString, true),
            base64_decode(env('KRAKEN_PRIVATE_KEY')),
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
