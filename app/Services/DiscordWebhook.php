<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DiscordWebhook
{

    /**
     * Send a message to the Discord webhook.
     *
     * @param string $message
     * @return void
     * @throws GuzzleException
     * @throws \Exception
     */
    public function sendMessage(string $message)
    {

        $webhookUrl = env('DISCORD_ALERT_WEBHOOK');
        $client = new Client();


        $payload = [
            'content' => $message
        ];

        $response = $client->post($webhookUrl, [
            'json' => $payload
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new \Exception('Failed to send message to Discord webhook.');
        }
    }
}
