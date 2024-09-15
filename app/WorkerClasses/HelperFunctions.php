<?php

namespace App\WorkerClasses;

use App\Models\AdminDashboard;
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

    // btc to satoshi
    public function btcToSatoshi($btc) {
        return $btc * 100000000;
    }

    // satoshi to btc
    public function satoshiToBtc($satoshi) {
        return $satoshi / 100000000;
    }

    public function getOnlineProviders(): array
    {
        $adminDashboard = AdminDashboard::first();
        $raw = json_decode($adminDashboard->provider_statuses, true);
        $providers = [];
        // get keys of the array where the value is not false
        foreach ($raw as $key => $value) {
            if ($value !== false) {
                $providers[] = $key;
            }
        }
        return $providers;
    }
}
