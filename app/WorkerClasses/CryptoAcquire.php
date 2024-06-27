<?php

namespace App\WorkerClasses;

class CryptoAcquire
{

    public function checkCryptoBalance() {

    }

    public function acquireCrypto($amount) {

    }

    private function getCryptoFromRobosats($amount) {
        $robosats = new Robosats();
        // this will try to find crypto with a premium the same as or lower than market rate
        $response = $robosats->getNegativePremiumBuyOffers(0);

        // ...

        // if can't find any offers, buy from binance

    }

}
