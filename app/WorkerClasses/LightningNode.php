<?php

namespace App\WorkerClasses;

use App\Models\AdminDashboard;
use Illuminate\Support\Facades\Http;

class LightningNode
{

    public string $endpoint = 'http://192.168.0.18:2101';
    public array $headers = [
        "Host" => "192.168.0.18:2101",
        "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0",
        "Accept" => "*/*",
        "Accept-Language" => "en-GB,en;q=0.5",
        "Accept-Encoding" => "gzip, deflate",
        "Referer" => "http://192.168.0.18:2101/",
        "Content-Type" => "application/json",
        "Connection" => "keep-alive",
        "Priority" => "u=4",
        "Pragma" => "no-cache",
        "Cache-Control" => "no-cache"
    ];

    public function getHeaders(): array
    {
        $currentHeaders = $this->headers;
        $adminDash = AdminDashboard::all()->first();
        $currentHeaders['Cookie'] = 'UMBREL_PROXY_TOKEN=' . $adminDash->umbrel_token;
        return $currentHeaders;
    }

    public function __construct($endpoint = null, $headers = null)
    {
        // set endpoint and headers maybe?
    }

    public function requestUrl($url)
    {
        $url = $this->endpoint . $url;

        try {
            $response = Http::withHeaders($this->getHeaders())->timeout(30)->get($url);
        } catch (\Exception $e) {
            return null;
        }

        return json_decode($response->body(), true);
    }

    public function getCurrentUSDPrice()
    {
        return $this->requestUrl('/v1/external/price');
    }

    public function getLightningWalletBalance()
    {
        $response = $this->requestUrl('/v1/lnd/channel');
        $localBalance = 0;
        $remoteBalance = 0;
        if (!$response) {
            return ['localBalance' => 0, 'remoteBalance' => 0, 'channelBalances' => []];
        }

        //  value [localBalance => value, remoteBalance => value]
        $channelBalances = [];

        foreach ($response as $channel) {
            $localBalance += $channel['localBalance'];
            $remoteBalance += $channel['remoteBalance'];
            $add = [
                'channelName' => $channel['remoteAlias'],
                'localBalance' => $channel['localBalance'],
                'remoteBalance' => $channel['remoteBalance']
            ];
            $channelBalances[] = $add;
        }
        return [
            'localBalance' => $localBalance,
            'remoteBalance' => $remoteBalance,
            'channelBalances' => $channelBalances
        ];
    }


    public function getInvoiceDetails($invoice)
    {
        // {"destination":"0259ad32e1e452ce189faa0131f6c76d3b54567c4fa665dc61fdc79355b60c98ba","paymentHash":"45dc8ee2c12a17bc5fe15a510673a91cd61de4257899b51af152292fae92b686","numSatoshis":"2000","timestamp":"1719526482","expiry":"86400","description":"","descriptionHash":"","fallbackAddr":"","cltvExpiry":"72","routeHints":[{"hopHints":[{"nodeId":"03a6ce61fcaacd38d31d4e3ce2d506602818e3856b4b44faff1dde9642ba705976","chanId":"16574444564780796506","feeBaseMsat":100,"feeProportionalMillionths":1500,"cltvExpiryDelta":9}]}],"paymentRequest":"lnbc20u1pn8mezjpp5ghwgackp9gtmchlptfgsvuafrntpmep90zvm2xh32g5jlt5jk6rqdqqcqzzgxqyz5vqrzjqwnvuc0u4txn35cafc7w94gxvq5p3cu9dd95f7hlrh0fvs46wpvhdesygxzrj2w2tgqqqqryqqqqthqqpysp53wh2jg6k83kdntaelutzdxtxwxnkevszdec6p0gg0ggk52ds2w0q9qrsgq8ctexfelzrn5tdhh53nertza4zufms482stn0cwmzqz7dqx0phpkrxp0psk75v2cfjdey3sx9cl5eyqcvfjrcyxwqmp877s2pjpq5hqpx009p0"}
        return $this->requestUrl('/v1/lnd/lightning/invoice?paymentRequest=' . $invoice);
    }

    public function payInvoice($invoice)
    {
        if (!$invoice) {
            return "No invoice provided";
        }
        // {"paymentError":"","paymentPreimage":{"0":161,"1":223,"2":234,"3":220,"4":241,"5":28,"6":177,"7":230,"8":80,"9":89,"10":224,"11":227,"12":118,"13":93,"14":92,"15":58,"16":242,"17":152,"18":193,"19":43,"20":108,"21":91,"22":87,"23":180,"24":57,"25":0,"26":248,"27":8,"28":73,"29":129,"30":140,"31":64},"paymentRoute":{"totalTimeLock":849966,"totalFees":"4","totalAmt":"2004","hops":[{"chanId":"934272622400634881","chanCapacity":"2000000","amtToForward":"2003","fee":"1","expiry":849822,"amtToForwardMsat":"2003100","feeMsat":"1001","pubKey":"02f1a8c87607f415c8f22c00593002775941dea48869ce23096af27b0cfdcc0b69","tlvPayload":true},{"chanId":"908076757863956480","chanCapacity":"11977735","amtToForward":"2000","fee":"3","expiry":849813,"amtToForwardMsat":"2000000","feeMsat":"3100","pubKey":"03a6ce61fcaacd38d31d4e3ce2d506602818e3856b4b44faff1dde9642ba705976","tlvPayload":true},{"chanId":"16574444564780796506","chanCapacity":"2000","amtToForward":"2000","fee":"0","expiry":849813,"amtToForwardMsat":"2000000","feeMsat":"0","pubKey":"0259ad32e1e452ce189faa0131f6c76d3b54567c4fa665dc61fdc79355b60c98ba","tlvPayload":true}],"totalFeesMsat":"4101","totalAmtMsat":"2004101"},"paymentHash":{"0":69,"1":220,"2":142,"3":226,"4":193,"5":42,"6":23,"7":188,"8":95,"9":225,"10":90,"11":81,"12":6,"13":115,"14":169,"15":28,"16":214,"17":29,"18":228,"19":37,"20":120,"21":153,"22":181,"23":26,"24":241,"25":82,"26":41,"27":47,"28":174,"29":146,"30":182,"31":134}}

        // post request
        $url = $this->endpoint . '/v1/lnd/lightning/payInvoice';

        $response = Http::withHeaders($this->getHeaders())->post($url, [
            'paymentRequest' => $invoice,
            'amt' => 0,
        ]);
        dd($response->body());

        // dd($response->body());

        return json_decode($response->body(), true);
    }

    public function getChannelDetails()
    {
        // [{"active":true,"remotePubkey":"02f1a8c87607f415c8f22c00593002775941dea48869ce23096af27b0cfdcc0b69","channelPoint":"28a79e93954780a820a2af4a726a9f4104de3c1e6b48591f53fd2ec475e0c1f3:1","chanId":"934272622400634881","capacity":"2000000","localBalance":"1829453","remoteBalance":"169887","commitFee":"2811","commitWeight":"1116","feePerKw":"2500","unsettledBalance":"0","totalSatoshisSent":"169887","totalSatoshisReceived":"0","numUpdates":"11","pendingHtlcs":[],"csvDelay":240,"private":false,"initiator":true,"chanStatusFlags":"ChanStatusDefault","localChanReserveSat":"20000","remoteChanReserveSat":"20000","staticRemoteKey":false,"type":"OPEN","remoteAlias":"Kraken 🐙⚡"}]
        return $this->requestUrl('/v1/lnd/channel');
    }

    public function getPayments() {
        // /v1/lnd/lightning/payments
        return $this->requestUrl('/v1/lnd/lightning/payments');
    }

    public function createInvoice($satoshis, $memo) {
        // post http://umbrel.local:2101/v1/lnd/lightning/addInvoice
        $url = $this->endpoint . '/v1/lnd/lightning/addInvoice';
        $response = Http::withHeaders($this->getHeaders())->timeout(30)->post($url, [
            'amt' => $satoshis,
            'memo' => $memo,
        ]);
        $json = json_decode($response->body(), true);
        // grab the payment request
        return $json['paymentRequest'];
    }


}
