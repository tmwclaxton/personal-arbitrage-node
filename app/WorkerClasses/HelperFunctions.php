<?php

namespace App\WorkerClasses;

use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\Transaction;
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
        if ($raw === null) {
            return [];
        }
        foreach ($raw as $key => $value) {
            if ($value !== false) {
                $providers[] = $key;
            }
        }
        return $providers;
    }

    public function generateSlug($length = 8): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $slug = '';
        for ($i = 0; $i < $length; $i++) {
            $slug .= $characters[rand(0, $charactersLength - 1)];
        }
        return $slug;
    }

    public function calcSatsInTransit() {
        $transactions = Transaction::all();
        $lightningNode = new LightningNode();
        $bondSatoshis = 0;
        $escrowSatoshis = 0;
        // grab all offers where bond_locked is true and status is less than 14
        $bondLockedOffers = Offer::where([['status', '<', 14], ['status', '>', 0], ['status', '!=', 4],['status', '!=', 5],['my_offer', '=', true]])
            ->orWhere([['status', '<', 14], ['status', '>', 2], ['status', '!=', 4],['status', '!=', 5],['accepted', '=', true]])
            ->get();
        foreach ($bondLockedOffers as $bondLockedOffer) {
            $transaction = $transactions->where('offer_id', $bondLockedOffer->id)->first();
            $bondInvoice = $transaction->bond_invoice;
            if ($bondInvoice) {
                $bondSatoshis += intval($lightningNode->getInvoiceDetails($bondInvoice)['numSatoshis']);
            }
        }

        // grab all offers where escrow_locked is true and status is less than 14
        $escrowLockedOffers = Offer::where([['status', '<', 14], ['status', '>', 0], ['status', '!=', 5]])
            ->orWhere([['status', '<', 14], ['status', '>', 2], ['status', '!=', 5],['accepted', '=', true]])
            ->get();
        foreach ($escrowLockedOffers as $escrowLockedOffer) {
            $transaction = $transactions->where('offer_id', $escrowLockedOffer->id)->first();
            $escrowInvoice = $transaction->escrow_invoice;
            if ($escrowInvoice) {
                $escrowSatoshis += intval($lightningNode->getInvoiceDetails($escrowInvoice)['numSatoshis']);
            }
        }

        $satsInTransit = $bondSatoshis + $escrowSatoshis;

        return $satsInTransit;
    }
}
