<?php

namespace App\WorkerClasses;

use App\Models\BtcFiat;

class HelperFunctions
{
    // helper function to convert satoshi to fiat
    public function satoshiToFiat($satoshi, $btcPriceInCurrency) {
        $fiat = $satoshi / 100000000 * $btcPriceInCurrency;
        return $fiat;
    }

    public function fiatToSatoshi($fiat, $btcPriceInCurrency) {
        $satoshi = $fiat / $btcPriceInCurrency * 100000000;
        return $satoshi;
    }
}
