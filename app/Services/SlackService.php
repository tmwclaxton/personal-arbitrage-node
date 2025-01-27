<?php

namespace App\Services;

use App\Models\AdminDashboard;
use App\Models\KitMessage;
use App\Models\Offer;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
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

    // Retry helper function

    /**
     * @throws \Exception
     */
    protected function retry(callable $callback, int $retries = 5, int $delay = 4): mixed
    {
        $attempt = 0;
        while ($attempt < $retries) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt >= $retries) {
                    throw $e; // rethrow exception if maximum attempts are reached
                }
                sleep($delay); // wait before retrying
            }
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    public function createChannel($channelName, $offer = null): string
    {
        // lowercase the channel name
        $channelName = strtolower($channelName);
        // add some random string to the channel name to avoid conflicts
        $channelName .= '-' . substr(md5(uniqid()), 0, 5);
        $slackService = new SlackService();

        // Retry the channel creation
        $channel = $slackService->client->conversationsCreate(['name' => $channelName]);

        $channelID = $channel->getChannel()->getId();
        if ($offer) {
            // make sure the id is saved to the offer
            $offer->slack_channel_id = $channelID;
            $offer->save();
        }

        sleep(3);

        // Retry fetching users
        $users = $this->retry(function () use ($slackService) {
            return $slackService->client->usersList();
        });

        // get members who are not bots and are not deactivated
        $members = $users->getMembers();

        $members = collect($members)->filter(function ($member) {
            return !$member->getIsBot() && !$member->getDeleted() && $member->getId() != 'USLACKBOT';
        })->values();

        // Filter out bots
//        foreach ($members as $key => $member) {
//            if ($member->getIsBot() || $member->getId() == 'USLACKBOT') {
//                unset($members[$key]);
//            }
//        }

        // Retry adding users to the channel
        foreach ($members as $member) {
            $this->retry(function () use ($slackService, $channelID, $member) {
                $slackService->client->conversationsInvite([
                    'channel' => $channelID,
                    'users' => $member->getId(),
                ]);
            });
            sleep(5);
        }

        return $channelID;
    }

    public function renameChannel($newName, $channelId): void
    {
        // lowercase the channel name
        $newName = strtolower($newName) . '-' . substr(md5(uniqid()), 0, 5);
        $this->retry(function () use ($channelId, $newName) {
            $this->client->conversationsRename([
                'channel' => $channelId,
                'name' => $newName,
            ]);
        });
    }

    /**
     * @throws \Exception
     */
    public function getLatestMessages($channelId): array
    {
        try {
            $messages = $this->retry(function () use ($channelId) {
                return $this->client->conversationsHistory([
                    'channel' => $channelId,
                    'limit' => 20,
                ])->getMessages();
            });
        } catch (\Exception $e) {
            return [];
        }

        return $messages;
    }

    /**
     * @throws \Exception
     */
    public function sendMessage(string $message, string $channelId = null, string $format = 'text', string $messageType = "message"): void
    {
        // if message is empty, return
        if (empty($message)) {
            return;
        }

        $kitMessage = new KitMessage();
        $kitMessage->channel_id = $channelId;
        $kitMessage->message = $message;
        $kitMessage->type = $messageType;
        $kitMessage->save();

        if (!$channelId) {
            $adminDashboard = AdminDashboard::all()->first();
            $channelId = $adminDashboard->slack_main_channel_id;
        }

        if ($format === 'bold') {
            $message = '*' . $message . '*';
        }

        if ($format === 'italic') {
            $message = '_' . $message . '_';
        }

        if ($format === 'blockquotes') {
            $message = '>' . $message;
        }

        $this->retry(function () use ($message, $channelId) {
            $this->client->chatPostMessage([
                "channel" => $channelId,
                "text" => $message,
            ]);
        });
    }

    /**
     * @throws \Exception
     */
    public function deleteChannel($channelId): void
    {
        $this->retry(function () use ($channelId) {
            $this->client->conversationsArchive([
                'channel' => $channelId,
            ]);
        });
    }


    // list users
    public function listBotUsers(): array
    {
        $users = $this->client->usersList()->getMembers();
        $botUsers = [];
        foreach ($users as $user) {
            if ($user->getIsBot()) {
                $botUsers[] = $user;
            }
        }

        return $botUsers;
    }

    // list channels made by the bot
    public function archiveOldBotChannels($cursor = null, $repeats = 0): string
    {

        $botUserId = $this->listBotUsers()[0]->getId();

        sleep(1);

        $data = [
            'exclude_archived' => true,
            'limit' => 300,
        ];

        if ($cursor) {
            $data['cursor'] = $cursor;
        }


        $convoData = $this->client->conversationsList($data);

        $responseMetadata = $convoData->getResponseMetadata();

        $nextCursor = $responseMetadata->getNextCursor();

        $channels = $convoData->getChannels();

        $channelsToArchive = [];

         foreach ($channels as $channel) {
             // if the channel was created by the bot and is older than 1 day (the timestamp is an epoch) and check that no offers have a slack channel id that matches this channel
            if ($channel->getCreator() === $botUserId && $channel->getCreated() < strtotime('-10 minutes')) {
//                && !Offer::where('slack_channel_id', $channel->getId())->exists()) {
                $channelsToArchive[] = $channel;
            }
            sleep(1);
        }

         // archive the channels
        foreach ($channelsToArchive as $channel) {
            $this->client->conversationsArchive([
                'channel' => $channel->getId(),
            ]);
            sleep(1);
        }

        Log::info('Archived ' . count($channelsToArchive) . ' channels, with repeat count at ' . $repeats);

        if ($nextCursor && $repeats > 0) {
            sleep(10);
            $this->archiveOldBotChannels($nextCursor, $repeats--);
        }


        return "done";
    }
}
