<?php

namespace App\Services;

use Google\Service\Gmail\Message;
use Google_Service_Gmail;
use Illuminate\Support\Facades\Redis;
use Webklex\IMAP\Facades\Client;

class GmailService
{

    protected $client;
    protected $service;

    protected const REDIS_ACCESS_TOKEN_KEY = "google.access_token";
    protected const REDIS_REFRESH_TOKEN_KEY = "google.refresh_token";


    public function __construct($service_prefix)
    {
        $this->service_prefix = $service_prefix;
        $this->client = new \Google_Client();
        $this->client->setAuthConfig([
            "installed" => [
                "client_id" => config('services.google.client_id'),
                "client_secret" => config('services.google.client_secret'),
            ]
        ]);

        $this->client->setRedirectUri('http://localhost:80/gmail_'.$service_prefix.'_redirect');

        $this->client->addScope([\Google_Service_Gmail::GMAIL_READONLY, Google_Service_Gmail::GMAIL_SEND]);
        $this->client->setAccessType('offline');  // To get a refresh token
        $this->client->setPrompt('select_account consent'); // Prompt to allow consent
    }

    public function getClient(){
        return $this->client;
    }

    private function getAccessToken(): string   {
        return Redis::get($this->service_prefix.".".$this::REDIS_ACCESS_TOKEN_KEY);
    }

    private function getRefreshToken(): string    {
        return Redis::get($this->service_prefix.".".$this::REDIS_REFRESH_TOKEN_KEY);
    }

    private function setAccessToken(string $accessToken)    {
        return Redis::set($this->service_prefix.".".$this::REDIS_ACCESS_TOKEN_KEY, $accessToken);
    }

    private function setRefreshToken(string $refreshToken)    {
        return Redis::set($this->service_prefix.".".$this::REDIS_REFRESH_TOKEN_KEY, $refreshToken);
    }

    private function getActiveAccessToken(): string {
        $accessToken = $this->getAccessToken();
        $this->client->setAccessToken($accessToken);

        if ($this->client->isAccessTokenExpired()) {
            $accessToken = $this->refreshAccessToken();
        }
        return $accessToken;
    }

    public function refreshAccessToken()
    {
        $this->client->refreshToken($this->getRefreshToken());
        return $this->client->getAccessToken()['access_token'];
    }

    public function exchangeCode(string $code){
        $tokens = $this->getClient()->fetchAccessTokenWithAuthCode($code);

        $this->setAccessToken($tokens['access_token']);
        $this->setRefreshToken($tokens['refresh_token']);
    }

    public function getService()
    {
        if (!$this->service) {
            $this->service = new \Google_Service_Gmail($this->client);
        }
        return $this->service;
    }


    public function fetchInboxMessages($senderEmail = null, $newer_than = "2h")
    {
        $this->client->setAccessToken($this->getActiveAccessToken());
        $service = new Google_Service_Gmail($this->client);

        // Gmail query to filter emails
        $query = '';
        if ($senderEmail) {
            $query .= "newer_than:$newer_than";
        }
        if ($senderEmail) {
            $query .= "newer_than:$newer_than";
        }

        // Get a list of message IDs that match the query
        $messagesResponse = $service->users_messages->listUsersMessages('me', [
            'q' => $query,
            'maxResults' => 10 // Adjust as needed
        ]);

        $messages = [];

        if ($messagesResponse->getMessages()) {
            foreach ($messagesResponse->getMessages() as $msg) {
                // Fetch full message content
                $message = $service->users_messages->get('me', $msg->getId());

                $emailData = [
                    'id' => $message->getId(),
                    'snippet' => $message->getSnippet(),
                    'body' => $this->getEmailBody($message),
                    'headers' => $this->getEmailHeaders($message)
                ];

                $messages[] = $emailData;
            }
        }

        return $messages;
    }

    public function sendEmail($recipient, $subject, $body){
        $this->client->setAccessToken($this->getActiveAccessToken());
        $service = new Google_Service_Gmail($this->client);

        // Encode email
        $message = "To: $recipient\r\n";
        $message .= "Subject: $subject\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
        $message .= $body;

        $rawMessage = base64_encode($message);
        $rawMessage = str_replace(['+', '/', '='], ['-', '_', ''], $rawMessage);

        // Create and send email
        $email = new Message();
        $email->setRaw($rawMessage);
        $service->users_messages->send('me', $email);
    }



    public function redirectToGoogle()
    {
        $gmailService = new GmailService($this->service_prefix);
        $authUrl = $gmailService->getClient()->createAuthUrl();
//        dd($authUrl);
        return redirect()->away($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $gmailService = new GmailService($this->service_prefix);

        // Authenticate and get the token
        $accessToken = $gmailService->client->fetchAccessTokenWithAuthCode($request->input('code'));

        // Store the access and refresh tokens for future use
        session(['gmail_access_token' => $accessToken]);

        return redirect()->route('gmail.inbox');
    }

    private function getEmailBody($message)
    {
        $payload = $message->getPayload();
        $body = '';

        // Check if message has parts (multipart emails)
        if ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                if ($part->getMimeType() === 'text/plain') {
                    $body = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                    break;
                }
            }
        } else {
            // Single-part message
            $body = base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
        }

        return trim($body);
    }

    private function getEmailHeaders($message)
    {
        $headers = [];
        foreach ($message->getPayload()->getHeaders() as $header) {
            $headers[$header->getName()] = $header->getValue();
        }
        return $headers;
    }



    public function parseFirstNovelLinkFromEmails($emails, $link_base, $expiry = 7200): string | null {
        foreach ($emails as $email) {
            if (isset($email['body'])) {
                if (preg_match($link_base.'[^\s"]*/', $email['body'], $matches)) {
                    // Check if link has already been used
                    if (!Redis::exists($this->service_prefix.".link:".$matches[0])) {
                        Redis::set($this->service_prefix.".link:".$matches[0], true, 'EX', $expiry);
                        return $matches[0];
                    }
                }
            }
        }
        return null;
    }



















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
        $gmailService = new GmailService($this->service_prefix);
        $text = $gmailService->getLastEmail();

        $link = $gmailService->grabLink($text, $start);
        if ($link === null) {
            $slackService = new SlackService();
            $slackService->sendMessage("Link not found in email");
            return json_encode(['error' => 'Link not found']);
        }

        return json_encode(['link' => $link]);
    }
}
