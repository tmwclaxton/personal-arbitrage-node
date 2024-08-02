<?php

namespace App\Jobs;

use App\Models\Offer;
use App\Services\PgpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetRobosatsMessages implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $offers = Offer::where('status', '=', 9)->get();

        foreach ($offers as $offer) {

            $robot = $offer->robots()->first();

            $b91 = new \Katoga\Allyourbase\Base91();
            $decoded = $b91->decode($robot->sha256);
            $hex = bin2hex($decoded);
            $url = 'ws://192.168.0.18:12596' . '/mainnet/' . $offer->provider . '/ws/chat/' . $offer->robosatsId . '/?token_sha256_hex=' . $hex;

            // create a new client
            $client = new \WebSocket\Client($url);
            $messages = [];

            $client->text(json_encode([
                'type' => 'message',
                'message' => $robot->public_key,
                'nick' => $robot->nickname
            ]));

            $client->text(json_encode([
                'type' => 'message',
                'message' => '-----SERVE HISTORY-----',
                'nick' => $robot->nickname
            ]));

            $startTime = time();
            $duration = 10; // Duration in seconds

            try {
                while (true) {
                    try {
                        $message = $client->receive();
                        if ($message) {
                            $messages[] = $message;
                        }
                    } catch (ConnectionException $e) {
                        // Handle timeout or connection error
                        sleep(1);
                        break;
                    }

                    // Exit the loop after 15 seconds
                    if (time() - $startTime > $duration) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                echo "Error: " . $e->getMessage();
            }

            // filter messages for opcode text
            $messages = array_filter($messages, function ($message) {
                return $message->getOpcode() == "text";
            });

            // each message is type WebSocket\Message\Text and we need to grab the content property first
            $messages = array_map(function ($message) {
                return $message->getContent();
            }, $messages);

            // filter messages if they have a key of 'index'
            $messages = array_filter($messages, function ($message) {
                $message = json_decode($message, true);
                return array_key_exists('index', $message);
            });

            // sort messages by index
            usort($messages, function ($a, $b) {
                $a = json_decode($a, true);
                $b = json_decode($b, true);
                return $a['index'] <=> $b['index'];
            });

            // decrypt all messages with my private key
            $pgpService = new PgpService();
            $privateKey = $robot->private_key;

            foreach ($messages as $message) {
                $message = json_decode($message, true);
                $content = $message['message'];
                // user_nick and
                $content = str_replace("\\", "\n", $content);
                $decodedMessage = $pgpService->decrypt($privateKey, $content, $robot->token);

                // check if index and offer_id exists already
                if (\App\Models\RobosatsChatMessage::where('offer_id', $offer->id)->where('index', $message['index'])->exists()) {
                    continue;
                }

                $robosatsChatMessage = new \App\Models\RobosatsChatMessage([
                    'offer_id' => $offer->id,
                    'index' => $message['index'],
                    'user_nick' => $message['user_nick'],
                    'sent_at' => $message['time'],
                    'message' => $decodedMessage
                ]);
                $robosatsChatMessage->save();

                $discordService = new \App\Services\DiscordService();
                $discordService->sendMessage("**New message in chatroom for offer ID: " . $offer->id . "**\n" . $decodedMessage);
            }
        }
    }
}
