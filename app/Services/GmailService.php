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
        return $inbox->messages()->where('FROM', 'noreply@kraken.com')->all()->limit(1, $totalMessages->total())->get()[0]->getBodies()['text'];
    }

    public function grabLink($text)
    {
        $link = null;
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            if (str_contains($line, 'https://www.kraken.com/new-device-sign-in/web?code=')) {
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
}
