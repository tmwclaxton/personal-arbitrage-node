<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DiscordService
{
    protected $botToken;
    protected $applicationId;
    protected $publicKey;
    protected $channelId;
    protected $client;

    public function __construct()
    {
        $this->botToken = env('DISCORD_API_BOT_TOKEN');
        $this->applicationId = env('DISCORD_APPLICATION_ID');
        $this->publicKey = env('DISCORD_PUBLIC_KEY');
        $this->channelId = env('DISCORD_CHANNEL_ID');
        $this->client = new Client([
            'base_uri' => 'https://discord.com/api/',
            'headers' => [
                'Authorization' => 'Bot ' . $this->botToken,
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    /**
     * Get the last 50 messages from the Discord channel.
     *
     * @return array
     * @throws GuzzleException
     */
    public function getLastMessages()
    {
        $response = $this->client->get("channels/{$this->channelId}/messages", [
            'query' => ['limit' => 50]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Send a message to the Discord channel.
     *
     * @param string $message
     * @return void
     * @throws GuzzleException
     * @throws \Exception
     */
    public function sendMessage(string $message)
    {
        $payload = [
            'content' => $message
        ];

        $response = $this->client->post("channels/{$this->channelId}/messages", [
            'json' => $payload
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to send message to Discord channel.');
        }
    }

    /**
     * Detect if a command is called in the messages.
     *
     * @param string $command
     * @return bool
     * @throws GuzzleException
     */
    public function detectCommand(string $command)
    {
        $messages = $this->getLastMessages();

        foreach ($messages as $message) {
            if (str_starts_with($message['content'], $command)) {
                return true;
            }
        }

        return false;
    }
}
