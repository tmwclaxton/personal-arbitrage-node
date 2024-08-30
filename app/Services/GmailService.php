<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Webklex\IMAP\Facades\Client;

class GmailService
{


    public function getLastEmail()
    {
        $client = Client::account("default");
        $client->connect();

        $folders = $client->getFolders(false);
        $inbox = null;
        // iterate through all folders to find one with path "INBOX"
        foreach ($folders as $folder) {
            if ($folder->name === "INBOX") {
                $inbox = $folder;
            }
        }

        $totalMessages = $inbox->messages()->where('FROM', 'noreply@kraken.com')->all()->limit(1, 0)->get();
        $messages = $inbox->messages()->where('FROM', 'noreply@kraken.com')->all()->limit(1, $totalMessages->total())->get();
        $message = $messages[0]->getBodies()['text'];
        return $message;

    }

    public function grabLink($text, $start): ?string
    {
        $link = null;
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            if (str_contains($line, $start)) {
                $link = $line;

                // remove anything before the https
                $link = explode("https", $link)[1];
                $link = "https" . $link;

                // remove any whitespace
                $link = preg_replace('/\s+/', '', $link);

                // the last period that should exist is in the .com if there are any periods after that, remove them and anything after them
                $comIndex = strpos($link, '.com');
                $lastPeriod = strrpos($link, '.');
                if ($lastPeriod > $comIndex) {
                    // remove everything after the last period
                    $link = substr($link, 0, $lastPeriod);

                    // remove any whitespace
                    $link = preg_replace('/\s+/', '', $link);
                }

                // remove the period at the end
                $link = rtrim($link, '.');

            }
        }
        // Check if link has already been used
        if (Redis::exists($link)) {
            // If the link exists in Redis, skip it
            $link = null;
        } else {
            // If the link does not exist, store it in Redis
            Redis::set($link, true);
            // Optionally, you can set an expiration time for the link
            // Redis::expire($link, 3600); // expires in 1 hour
        }

        return $link;
    }

    public function getLinkFromLastEmail($start = 'https://www.kraken.com/new-device-sign-in/web?code='): ?string
    {

        // grab email
        $gmailService = new \App\Services\GmailService();
        $text = $gmailService->getLastEmail();

        $link = $gmailService->grabLink($text, $start);
        if ($link === null) {
            $discordService = new \App\Services\DiscordService();
            $discordService->sendMessage("Link not found in email");
            return json_encode(['error' => 'Link not found']);
        }

        return json_encode(['link' => $link]);
    }
}
