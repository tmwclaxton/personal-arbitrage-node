<?php

namespace App\WorkerClasses;

use App\Models\BtcFiat;

class HelperFunctions
{
    // helper function to convert satoshi to fiat
    public function satoshiToFiat($satoshi, $currency) {
        $btcFiats = BtcFiat::all();
        $btcFiat = $btcFiats->where('currency', $currency)->first();
        $fiat = $satoshi / 100000000 * $btcFiat->price;
        return $fiat;
    }

    public function fiatToSatoshi($fiat, $currency) {
        $btcFiats = BtcFiat::all();
        $btcFiat = $btcFiats->where('currency', $currency)->first();
        $satoshi = $fiat / $btcFiat->price * 100000000;
        return $satoshi;
    }
}
