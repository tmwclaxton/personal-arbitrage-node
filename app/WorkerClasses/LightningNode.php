<?php

namespace App\WorkerClasses;

class LightningNode
{

    public string $endpoint = 'http://';

    public function __construct($endpoint)
    {
        $this->endpoint = $endpoint;
    }


    public function sendToAddress($address, $amount)
    {

    }

    public function checkIfPaymentReceived($invoice)
    {

    }

    public function createInvoice($amount)
    {

    }



}
