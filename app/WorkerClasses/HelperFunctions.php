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
                $val = $lightningNode->getInvoiceDetails($bondInvoice);
                if ($val) {
                    $bondSatoshis += intval($val['numSatoshis']);
                } else {
                    $bondSatoshis += 0;
                }
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
                $val = $lightningNode->getInvoiceDetails($escrowInvoice);
                if ($val) {
                    $escrowSatoshis += intval($val['numSatoshis']);
                } else {
                    $escrowSatoshis += 0;
                }
            }
        }


        return ['bondSatoshis' => $bondSatoshis, 'escrowSatoshis' => $escrowSatoshis];
    }

    public function refreshUmbrelCommandCheck() {
        $adminDashboard = AdminDashboard::all()->first();
        if (isset($adminDashboard, $adminDashboard->umbrel_ip, $adminDashboard->umbrel_password)) {
            return true;
        } else {
            return false;
        }
    }

    public function normalUmbrelCommandCheck() {
        $adminDashboard = AdminDashboard::all()->first();
        if (isset($adminDashboard, $adminDashboard->umbrel_ip, $adminDashboard->umbrel_token)) {
            return true;
        } else {
            return false;
        }
    }

    public function krakenCommandCheck() {
        $adminDashboard = AdminDashboard::all()->first();
        if (isset($adminDashboard, $adminDashboard->kraken_api_key, $adminDashboard->kraken_private_key)) {
            return true;
        } else {
            return false;
        }
    }

    public function slackCommandCheck() {
        $adminDashboard = AdminDashboard::all()->first();
        if (isset($adminDashboard, $adminDashboard->slack_app_id, $adminDashboard->slack_client_id, $adminDashboard->slack_client_secret, $adminDashboard->slack_signing_secret, $adminDashboard->slack_bot_token)) {
            return true;
        } else {
            return false;
        }
    }


}
