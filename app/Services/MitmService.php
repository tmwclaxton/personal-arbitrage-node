<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MitmService
{

    private string $endpoint = 'http://192.168.160.1:8081';

    public function __construct()
    {
        $this->endpoint = env('HOST_IP') . ':8081';
    }

    public function grabAll()
    {
        // http://localhost:8081/flows
        $url = $this->endpoint . '/flows';
        $response = Http::get($url)->json();

        // reponse is an array of flows
        // each flow is an array with keys: request, response (partial)
        // filter out flows with a host contiaining 'revolut'
        $revolutFlows = array_filter($response, function ($flow) {
            return strpos($flow['request']['host'], 'revolut') !== false;
        });

        // filter again if the path equals '/api/retail/user/current/transactions/last?count=20' or 'api/retail/user/current/wallet'
        $revolutFlows = array_filter($revolutFlows, function ($flow) {
            return in_array($flow['request']['path'], ['/api/retail/user/current/transactions/last?count=20', '/api/retail/user/current/wallet']);
        });

        // Order by timestamp_created descending
        usort($revolutFlows, function ($a, $b) {
            return $b['timestamp_created'] <=> $a['timestamp_created'];
        });

        // Grab the latest flow with a unique request.path
        $latestFlows = [];
        foreach ($revolutFlows as $flow) {
            $path = $flow['request']['path'];
            if (!isset($latestFlows[$path])) {
                $latestFlows[$path] = $flow;
            }
        }

        // foreach flow left grab the response from /flows/$flow['id']/response/content/Raw.json and append it to the flow
        $editedFlows = [];
        foreach ($latestFlows as $flow) {
            $response = Http::get($this->endpoint . '/flows/' . $flow['id'] . '/response/content/Raw.json')->json();
            $flow['actual_response'] = json_decode($response['lines'][0][0][1], true);
            $editedFlows[] = $flow;
        }

        return $editedFlows;
    }

    public function getBalances(): array
    {
        global $gbp_balance, $euro_balance, $usd_balance;

        $editedFlows = $this->grabAll();
        foreach ($editedFlows as $flow) {
            if ($flow['request']['path'] == '/api/retail/user/current/wallet') {
                $pockets = $flow['actual_response']['pockets'];
                // filter pockets for where the type is 'CURRENT'
                $pockets = array_filter($pockets, function($pocket) {
                    return $pocket['type'] === 'CURRENT';
                });

                foreach ($pockets as $pocket) {
                    switch ($pocket['currency']) {
                        case 'GBP':
                            $gbp_balance = $pocket['balance'] / 100;
                            break;
                        case 'EUR':
                            $euro_balance = $pocket['balance'] / 100;
                            break;
                        case 'USD':
                            $usd_balance = $pocket['balance'] / 100;
                            break;
                    }
                }
            }
        }

        // Round them though to 2 decimal places (.00)
        $gbp_balance = floor($gbp_balance);
        $euro_balance = floor($euro_balance);
        $usd_balance = floor($usd_balance);

        return ["GBP" => $gbp_balance, "EUR" => $euro_balance, "USD" => $usd_balance];
    }

    public function grabTransactions(): array
    {
        $editedFlows = $this->grabAll();
        $transactions = [];
        foreach ($editedFlows as $flow) {
            if ($flow['request']['path'] == '/api/retail/user/current/transactions/last?count=20') {

                // if t

                $transactions = $flow['actual_response'];
            }
        }

        return $transactions;
    }
}
