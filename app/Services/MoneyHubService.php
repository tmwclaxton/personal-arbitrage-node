<?php

namespace App\Services;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Mockery\Exception;

class MoneyHubService
{
    public const REDIS_PREFIX = "reporting";
    public const MONEYHUB_EMAIL = 'support@moneyhub.com';


    public function scrape()
    {

        $email = config('services.moneyhub.email');
        $password = config('services.moneyhub.password');

        // Setup Chrome options
        $options = new ChromeOptions();
        $options->addArguments([
//            "--headless", // Run Chrome in headless mode (no UI)
            "--no-sandbox", // Disable sandboxing
            "--disable-dev-shm-usage", // Prevents Docker issues
        ]);
        $downloadDirectory = "/";
        $options->setExperimentalOption('prefs', [
            'download.default_directory' => $downloadDirectory,
            'download.prompt_for_download' => false, // Avoid download prompt
            'directory_upgrade' => true,
        ]);

        // Create a DesiredCapabilities object for Chrome
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        // URL of your Selenium container (running on Docker)
        $selenium_url = "http://selenium:4444/wd/hub";  // This connects to the Docker container

//        dd("hi1");
        // Connect to the remote Selenium WebDriver
        $driver = RemoteWebDriver::create($selenium_url, $capabilities);
        try {

//dd("hi");
            // Navigate to example.com (you can change this URL)
            $driver->get("http://client.moneyhub.co.uk/");

//        $driver->wait()->until(
//            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::tagName('body'))
//        );

//        $wait = new WebDriverWait($driver, 10);
//        $wait->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::tagName("body")));


            // Find the email input field and enter the email
            $driver->wait(20)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('email'))
            );
            $emailField = $driver->findElement(WebDriverBy::id('email'));
            $emailField->clear(); // Clear any existing value
            $emailField->sendKeys($email); // Enter email

            // Find the password input field and enter the password
            $passwordField = $driver->findElement(WebDriverBy::id('password'));
            $passwordField->clear(); // Clear any existing value
            $passwordField->sendKeys($password); // Enter password

            // Find the submit button (assuming it's a button or input with type="submit")
            $submitButton = $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'));
            $submitButton->click(); // Click the submit button


            sleep(10);
            // Wait for the h1 with text "Dashboard" to appear
//        $driver->wait(40)->until(
//            WebDriverExpectedCondition::visibilityOfElementLocated(
//                WebDriverBy::xpath("//h1[text()='Dashboard']")
//            )
//        );

            $html = $driver->getPageSource();
            $title = $driver->getTitle();


            // Output the HTML
//        echo $html;

//        dd($html, $title);


            // Check if the "Verify your device" prompt is present
            if (str_contains($html, 'Verify your device')) {
                sleep(5); // Wait a few seconds for the OTP email to arrive

                // Get the most recent OTP
                $otp = $this->getMostRecentOTP();

                if ($otp) {
                    // Find the OTP input field and enter the OTP
                    $otpField = $driver->findElement(WebDriverBy::id('TOTP'));
                    $otpField->clear();
                    $otpField->sendKeys($otp);

                    // Find and click the verify button
                    $verifyButton = $driver->findElement(WebDriverBy::cssSelector('button[data-aid="signin-button"]'));
                    $verifyButton->click();
                    sleep(5);
                }
            }

            $html = $driver->getPageSource();
//        echo $html;


//
//    // Retrieve CSRF token from localStorage
//        $csrfTokenJson = $driver->executeScript("return window.localStorage.getItem('csrf-token');");
//        $csrfToken = json_decode($csrfTokenJson, true)['payload'] ?? null;
//
//        $tenantId = $driver->executeScript("return window.localStorage.getItem('tenant');");
//
//        // First request to retrieve cookies
//        $client = new Client();
//        $cookieJar = new CookieJar();
//        $response = $client->request('GET', "https://asm.moneyhub.co.uk/text/resources", [
//            'query' => [
//                'tenantId' => $tenantId,
//                'type' => 'prelogin',
//            ],
//            'headers' => [
//                'sec-ch-ua-platform' => '"Windows"',
//                'Referer' => '', // If needed, add the actual value for the Referer header
//                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
//                'sec-ch-ua' => '"Chromium";v="134", "Not:A-Brand";v="24", "Brave";v="134"',
//                'sec-ch-ua-mobile' => '?0',
//                ],
//            'cookies' => $cookieJar,
//        ]);
//        $driver->quit();
//
////        dd($response->getHeaders()['csrf-token'][0], $csrfToken, $cookieJar);
//    $csrfToken = $response->getHeaders()['csrf-token'][0];
//
//// Extract the 'sid' cookie from the response
//        $sidCookie = null;
//        foreach ($cookieJar->toArray() as $cookie) {
//            if (strpos($cookie['Name'], 'sid') === 0) {
//                $sidCookie = $cookie['Name'] . '=' . $cookie['Value'];
//                break;
//            }
//        }
////
//
//// Ensure we have the required tokens before making the request
//    if ($sidCookie && $csrfToken) {
//
//        try {
//            $response = $client->request('POST', 'https://asm.moneyhub.co.uk/user/csv-export', [
//                'headers' => [
//                    'accept' => 'application/json',
//                    'accept-language' => 'en-US,en;q=0.8',
//                    'authorization-mode' => 'v2',
//                    'content-type' => 'application/json',
//                    'csrf-token' => $csrfToken,
//                    'origin' => 'https://client.moneyhub.co.uk',
//                    'priority' => 'u=1, i',
//                    'sec-ch-ua' => '"Chromium";v="134", "Not:A-Brand";v="24", "Brave";v="134"',
//                    'sec-ch-ua-mobile' => '?0',
//                    'sec-ch-ua-platform' => '"Windows"',
//                    'sec-fetch-dest' => 'empty',
//                    'sec-fetch-mode' => 'cors',
//                    'sec-fetch-site' => 'same-site',
//                    'sec-gpc' => '1',
//                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
//                    'x-force-date' => 'undefined',
//                    'x-requested-with' => 'XMLHttpRequest',
//                    'x-yw-client' => '2.7.1',
//                    'x-yw-device-id' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
//                ],
//                'cookies' => CookieJar::fromArray([
//                    explode("=", $sidCookie)[0] => explode("=", $sidCookie)[1],
//                    "csrf-token" => $csrfToken
//                ], 'asm.moneyhub.co.uk'),
//                'json' => [
//                    'data' => [
//                        'accountUid' => '5991d74e-3570-42a8-b901-b8dd5710a7ef',
//                        'start' => Carbon::yesterday()->toDateString(),//YYYY-MM-DD format
//                        'end' => Carbon::today()->toDateString()
//                    ]
//                ]
//            ]);
//        } catch (RequestException $e){
//            $driver->quit();
//            dd($e->getMessage(), $e->getRequest(), $e->getResponse()->getBody()->getContents());        } finally {
//            $driver->quit();
//        }
//        $body = $response->getBody()->getContents();
//        $driver->quit();
//        return $body;
//    } else {
//        $driver->quit();
//        dd($cookies, $sidCookie, $csrfToken);
//        return "Session cookie or CSRF token not found.";
//    }


            $accountList = [
                [
                    "bankName" => "Monzo",
                    "currency" => "GBP",
                    "moneyHubUid" => "c564da3a-b433-448d-95c7-e4a9715b2916"
                ],
//                [
//                    "bankName" => "Revolut",
//                    "currency" => "GBP",
//                    "moneyHubUid" => "2dcea96d-ae4b-4a09-8285-ad312fd93f8d"
//                ],
//                [
//                    "bankName" => "Revolut",
//                    "currency" => "USD",
//                    "moneyHubUid" => "45b27fde-15ca-482f-9441-25efe01da407"
//                ],
//                [
//                    "bankName" => "Revolut",
//                    "currency" => "AUD",
//                    "moneyHubUid" => "1a02c32f-e4e0-4c97-9f6c-f0ca211b38c7"
//                ],
                [
                    "bankName" => "Revolut",
                    "currency" => "EUR",
                    "moneyHubUid" => "f25e7557-d1dc-41ac-86bb-09161f7ab4b0"
                ],
            ];

// Navigate to the transactions page
            $all_transaction_data = [];
            foreach ($accountList as $account) {


                $driver->get("https://client.moneyhub.co.uk/#transactions");
                sleep(2);
                $html = $driver->getPageSource();


                // Wait until the export button is visible and then click it using JavaScript
                $driver->wait(40)->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath('//button[@label="export"]'))
                );
                $exportButton = $driver->findElement(WebDriverBy::xpath('//button[@label="export"]'));
                $driver->executeScript("arguments[0].click();", [$exportButton]);

// Wait until the account select dropdown is visible and then click it
                $driver->wait(40)->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('account-select'))
                );

                // Select the account option (by its value)
                $accountSelect = $driver->findElement(WebDriverBy::id('account-select'));
                $select = new WebDriverSelect($accountSelect);
                $select->selectByValue($account["moneyHubUid"]);

// Select the date option (by its value)
//            $dateSelect = $driver->findElement(WebDriverBy::id('date-select'));
//            $selectDate = new WebDriverSelect($dateSelect);
//            $selectDate->selectByValue('2025-03-01---2025-03-15');

                $dateRange = Carbon::now()->startOfMonth()->toDateString() . "---" . Carbon::now()->toDateString();
                $dateSelect = $driver->findElement(WebDriverBy::id('date-select'));
                $selectDate = new WebDriverSelect($dateSelect);
                $selectDate->selectByValue($dateRange);

// Create a label for the date range
                $dateRange = Carbon::yesterday()->toDateString() .
                    "---" .
                    Carbon::today()->toDateString();

// JavaScript to directly set the value of the select dropdown
                $script = "
document.getElementById('date-select').value = '$dateRange';
";

// Execute the script to set the value
                $driver->executeScript($script);

// Wait until the download button is visible and then click it
                $driver->wait(40)->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath("//button[text()='Email as attachment']"))
                );
                $downloadButton = $driver->findElement(WebDriverBy::xpath("//button[text()='Email as attachment']"));
                $driver->executeScript("arguments[0].click();", [$downloadButton]);

                for ($x = 0; $x < 2; $x++){
                    sleep(5);
                    $transaction_data = $this->getMostRecentTransactionData();

                    if ($transaction_data != null) {
                        $all_transaction_data[$account['bankName']."-".$account['currency']] = $transaction_data;
                    }
                }
            }

            $driver->quit();
            return $all_transaction_data;

        } catch (\Throwable $e){
            $html = $driver->getPageSource();
            echo $html;
            $driver->close();

            throw $e;
            return 1;
        }



    }

    public function getMostRecentOTP($autoDelete = true) {
        $gmailService =  new GmailService(self::REDIS_PREFIX);
        $emails = $gmailService->fetchInboxMessages(self::MONEYHUB_EMAIL, "30m", 1);

        $text = $emails[0]['body'] ?? null;

        if(!$text){
            return null;
        }

        // Use regex to extract OTP (6-digit number)
        if (preg_match('/\b\d{6}\b/', $text, $matches)) {
            $messageId = $emails[0]['id'];
            $gmailService->deleteEmail($messageId);

            return $matches[0];
        } else {
            return false;
        }
    }

    public function getMostRecentTransactionData($onlyReceivingPayments = true, $autoDeleteEmail = true) {
        $gmailService = new GmailService(self::REDIS_PREFIX);
        $emails = $gmailService->fetchInboxMessages(self::MONEYHUB_EMAIL, "30m", 1, "CSV");

//        dd($emails);
        if (empty($emails)) {
//            dd("no emails", $emails);
            return null;
        }

        $messageId = $emails[0]['id'];
        $data = $gmailService->getCsvData($messageId);

        if ($onlyReceivingPayments) {
            $data = self::filterReceivingPayments($data);
        }

        if ($autoDeleteEmail){
            // Delete the email after processing
            $gmailService->deleteEmail($messageId);
        }

        return $data;
    }


    /**
     * @param array $transactions
     * @return array
     * filters out negative transfers
     * filters out transfers in from the user
     */
    public static function filterReceivingPayments(array $transactions)
    {
        $forbiddenStrings = [
            "Joshua Young",
            "Joshua Stephen Young",
            "Joshua S Young",
            "Joshua Y",
            "J Young",
        ];

        return array_filter($transactions, fn($t) =>
            !str_starts_with($t['AMOUNT'], "-") &&
            !array_reduce($forbiddenStrings, fn($carry, $str) => $carry || str_contains($t['DESCRIPTION'], $str), false)
        );
    }



}
