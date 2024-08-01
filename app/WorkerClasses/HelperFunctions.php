<?php

namespace App\WorkerClasses;

use App\Models\BtcFiat;
use Brick\Math\BigDecimal;

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

    public function bigDecimalToDecimal(BigDecimal $bigDecimal) {
        $value = $bigDecimal->getUnscaledValue()->toInt();
        $scale = $bigDecimal->getScale();
        return $value / 10 ** $scale;
    }

    // helper function to convert different currencies using the difference in btc price as the exchange rate
    public function convertCurrency($amount, $fromCurrency, $toCurrency) {
        $btcFiats = BtcFiat::all();
        $fromBtcPrice = $btcFiats->where('currency', $fromCurrency)->first()->price;
        $toBtcPrice = $btcFiats->where('currency', $toCurrency)->first()->price;
        $exchangeRate = $toBtcPrice / $fromBtcPrice;
        return $amount * $exchangeRate;
    }
}
