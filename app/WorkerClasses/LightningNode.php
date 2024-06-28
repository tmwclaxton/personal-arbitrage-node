<?php

namespace App\WorkerClasses;

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
        "Cookie" => "UMBREL_PROXY_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwcm94eVRva2VuIjp0cnVlLCJpYXQiOjE3MTk0MzI5MzQsImV4cCI6MTcyMDAzNzczNH0.31qKPyd1zRoySVRPVzisbTxO_FljIisBOHJFyJs6JYc",
        "Priority" => "u=4",
        "Pragma" => "no-cache",
        "Cache-Control" => "no-cache"
    ];

    public function __construct($endpoint = null, $headers = null)
    {
        // set endpoint and headers maybe?
    }

    public function requestUrl($url)
    {
        $url = $this->endpoint . $url;

        try {
            $response = Http::withHeaders($this->headers)->timeout(30)->get($url);
        } catch (\Exception $e) {
            return null;
        }

        return json_decode($response->body(), true);
    }

    public function getCurrentUSDPrice()
    {
        return $this->requestUrl('/v1/external/price');
    }


    public function getInvoiceDetails($invoice)
    {
        // {"destination":"0259ad32e1e452ce189faa0131f6c76d3b54567c4fa665dc61fdc79355b60c98ba","paymentHash":"45dc8ee2c12a17bc5fe15a510673a91cd61de4257899b51af152292fae92b686","numSatoshis":"2000","timestamp":"1719526482","expiry":"86400","description":"","descriptionHash":"","fallbackAddr":"","cltvExpiry":"72","routeHints":[{"hopHints":[{"nodeId":"03a6ce61fcaacd38d31d4e3ce2d506602818e3856b4b44faff1dde9642ba705976","chanId":"16574444564780796506","feeBaseMsat":100,"feeProportionalMillionths":1500,"cltvExpiryDelta":9}]}],"paymentRequest":"lnbc20u1pn8mezjpp5ghwgackp9gtmchlptfgsvuafrntpmep90zvm2xh32g5jlt5jk6rqdqqcqzzgxqyz5vqrzjqwnvuc0u4txn35cafc7w94gxvq5p3cu9dd95f7hlrh0fvs46wpvhdesygxzrj2w2tgqqqqryqqqqthqqpysp53wh2jg6k83kdntaelutzdxtxwxnkevszdec6p0gg0ggk52ds2w0q9qrsgq8ctexfelzrn5tdhh53nertza4zufms482stn0cwmzqz7dqx0phpkrxp0psk75v2cfjdey3sx9cl5eyqcvfjrcyxwqmp877s2pjpq5hqpx009p0"}
        return $this->requestUrl('/v1/lnd/lightning/invoice?paymentRequest=' . $invoice);
    }

    public function payInvoice($invoice)
    {
        // {"paymentError":"","paymentPreimage":{"0":161,"1":223,"2":234,"3":220,"4":241,"5":28,"6":177,"7":230,"8":80,"9":89,"10":224,"11":227,"12":118,"13":93,"14":92,"15":58,"16":242,"17":152,"18":193,"19":43,"20":108,"21":91,"22":87,"23":180,"24":57,"25":0,"26":248,"27":8,"28":73,"29":129,"30":140,"31":64},"paymentRoute":{"totalTimeLock":849966,"totalFees":"4","totalAmt":"2004","hops":[{"chanId":"934272622400634881","chanCapacity":"2000000","amtToForward":"2003","fee":"1","expiry":849822,"amtToForwardMsat":"2003100","feeMsat":"1001","pubKey":"02f1a8c87607f415c8f22c00593002775941dea48869ce23096af27b0cfdcc0b69","tlvPayload":true},{"chanId":"908076757863956480","chanCapacity":"11977735","amtToForward":"2000","fee":"3","expiry":849813,"amtToForwardMsat":"2000000","feeMsat":"3100","pubKey":"03a6ce61fcaacd38d31d4e3ce2d506602818e3856b4b44faff1dde9642ba705976","tlvPayload":true},{"chanId":"16574444564780796506","chanCapacity":"2000","amtToForward":"2000","fee":"0","expiry":849813,"amtToForwardMsat":"2000000","feeMsat":"0","pubKey":"0259ad32e1e452ce189faa0131f6c76d3b54567c4fa665dc61fdc79355b60c98ba","tlvPayload":true}],"totalFeesMsat":"4101","totalAmtMsat":"2004101"},"paymentHash":{"0":69,"1":220,"2":142,"3":226,"4":193,"5":42,"6":23,"7":188,"8":95,"9":225,"10":90,"11":81,"12":6,"13":115,"14":169,"15":28,"16":214,"17":29,"18":228,"19":37,"20":120,"21":153,"22":181,"23":26,"24":241,"25":82,"26":41,"27":47,"28":174,"29":146,"30":182,"31":134}}

        // post request
        $url = $this->endpoint . '/v1/lnd/lightning/payInvoice';
        $response = Http::withHeaders($this->headers)->timeout(30)->post($url, [
            'paymentRequest' => $invoice,
            'amt' => '0',
        ]);
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



}
