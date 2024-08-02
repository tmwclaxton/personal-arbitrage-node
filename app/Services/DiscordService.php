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
    public function getLatestMessages()
    {
        $stream = $this->client->get("channels/{$this->channelId}/messages", [
            'limit' => 50
        ]);

        // we receive a GuzzleHttp\Psr7\Stream object, so we need to convert it to an array
        $messages = json_decode($stream->getBody()->getContents(), true);

        // any messages with an author.id of 1258092511046668400 or 1257972131241791559 are from bots so remove them
        $messages = array_filter($messages, function ($message) {
            return $message['author']['id'] !== '1258092511046668400' && $message['author']['id'] !== '1257972131241791559';
        });

        return $messages;
    }

    /**
     * Send a message to the Discord channel.
     *
     * @param string $message
     * @return void
     * @throws GuzzleException
     * @throws \Exception
     */
    public function sendMessage(string $message, string $channelId = null): void
    {
        if ($channelId === null) {
            $channelId = $this->channelId;
        }

        $payload = [
            'content' => $message
        ];

        $response = $this->client->post("channels/{$channelId}/messages", [
            'json' => $payload
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to send message to Discord channel.');
        }
    }

    // public function checkIfMessageSentRecently(string $message): bool
    // {
    //     // recently means within the last 5 minutes
    //     $messages = $this->getLatestMessages();
    //     $recentMessages = array_filter($messages, function ($message) {
    //         return strtotime($message['timestamp']) > strtotime('-5 minutes');
    //     });
    //
    //     $recentMessages = array_filter($recentMessages, function ($message) use ($message) {
    //         return $message['content'] === $message;
    //     });
    //
    //     return count($recentMessages) > 0;
    //
    //
    // }

    // public function createChannel(string $name): void
    // {
    //     $payload = [
    //         'name' => $name,
    //         'type' => 0
    //     ];
    //
    //     $response = $this->client->post("guilds/{$this->applicationId}/channels", [
    //         'json' => $payload
    //     ]);
    //
    //     if ($response->getStatusCode() !== 201) {
    //         throw new \Exception('Failed to create channel.');
    //     }
    // }

}
