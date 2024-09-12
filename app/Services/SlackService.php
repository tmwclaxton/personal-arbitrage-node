<?php

namespace App\Services;

use App\Models\AdminDashboard;
use GuzzleHttp\Exception\GuzzleException;
use JoliCode\Slack\Api\Client;
use JoliCode\Slack\ClientFactory;

class SlackService
{
    protected mixed $slack_app_id;
    protected mixed $slack_client_id;
    protected mixed $slack_client_secret;
    protected mixed $slack_signing_secret;
    protected mixed $slack_bot_token;

    public Client $client;

    public function __construct()
    {
        $adminDashboard = AdminDashboard::all()->first();
        $this->slack_app_id = $adminDashboard->slack_app_id;
        $this->slack_client_id = $adminDashboard->slack_client_id;
        $this->slack_client_secret = $adminDashboard->slack_client_secret;
        $this->slack_signing_secret = $adminDashboard->slack_signing_secret;
        $this->slack_bot_token = $adminDashboard->slack_bot_token;

        $this->client = ClientFactory::create($adminDashboard->slack_bot_token);
    }


    public function createChannel($channelName): ?string
    {
        //!TODO: might be an idea to add retry logic here at some point
        $slackService = new SlackService();
        $channel = $slackService->client->conversationsCreate([
            'name' => $channelName,
        ]);
        $channelID = $channel->getChannel()->getId();

        sleep(3);

        // grab all users
        $users = $slackService->client->usersList();
        $members = $users->getMembers();

        // filter out bots
        foreach ($members as $key => $member) {
            if ($member->getIsBot()) {
                unset($members[$key]);
            }
            // check if id is USLACKBOT
            if ($member->getId() == 'USLACKBOT') {
                unset($members[$key]);
            }
        }

        // add users to channel
        foreach ($members as $member) {
            $slackService->client->conversationsInvite([
                'channel' => $channelID,
                'users' => $member->getId(),
            ]);
            sleep(5);
        }

        return $channelID;
    }

    /**
     * Get the last 50 messages from the Slack channel.
     *
     * @return array
     * @throws GuzzleException
     */
    public function getLatestMessages($channelId): array
    {
        $messages = $this->client->conversationsHistory([
            'channel' => $channelId,
            'limit' => 20,
        ]);

        return $messages->getMessages();
    }

    /**
     * Send a message to the Slack channel.
     *
     * @param string $message
     * @return void
     * @throws GuzzleException
     * @throws \Exception
     */
    public function sendMessage(string $message, string $channelId): void
    {
        $this->client->chatPostMessage([
            'channel' => $channelId,
            'text' => $message,
        ]);

        sleep(3);
    }

    public function deleteChannel($channelId): void
    {
        $this->client->conversationsArchive([
            'channel' => $channelId,
        ]);
    }
}