<?php

namespace App\Services;

use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverPlatform;
use Illuminate\Support\Carbon;

class SeleniumService
{
    private RemoteWebDriver $driver;
    public string $linkUsed;
    public function __construct()
    {

        $serverUrl = 'http://selenium:4444';
        $profile = new FirefoxProfile();
        $profile->setPreference('general.useragent.override', 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36');
        $desiredCapabilities = DesiredCapabilities::firefox();
        // $desiredCapabilities->setPlatform(WebDriverPlatform::WINDOWS);
        $desiredCapabilities->setCapability('acceptSslCerts', true);
        $desiredCapabilities->setCapability('firefox_profile', $profile);
        $this->driver = RemoteWebDriver::create($serverUrl, $desiredCapabilities);

        // $this->driver->get('https://www.kraken.com/sign-in');
    }

    public function __destruct()
    {
        $this->driver->quit();
    }

    public function getDriver(): \Facebook\WebDriver\Remote\RemoteWebDriver
    {
        return $this->driver;
    }

    public function signin($krakenService, $url = 'https://www.kraken.com/sign-in'): void
    {
        try {

            $this->driver->get($url);


            // Set window size to 1
            $this->driver->manage()->window()->setSize(new WebDriverDimension(1920, 1080));

            $this->driver->executeScript("window.scrollTo(0," . rand(0, 20) . ")");

            sleep(5);


            $linkValues = $this->getLinks();
            // if any of the links contain the text "Sign in" click it
            $text = "Sign in";
            if ( in_array($text, array_column($linkValues, 'text')) )
            {
                $this->clickLinksWithText($linkValues, ["Sign in"]);
            }
            sleep(rand(5,7));

            $usernameAttribute = $this->driver->findElement(WebDriverBy::name("username"));
            $passwordAttribute = $this->driver->findElement(WebDriverBy::name("password"));



            // Perform the actions from the JUnit code
            $usernameAttribute->click();
            $usernameAttribute->sendKeys(env('KRAKEN_USERNAME'));
            $passwordAttribute->sendKeys(env('KRAKEN_PASSWORD'));
            $passwordAttribute->sendKeys(WebDriverKeys::ENTER);


            // $buttons = $this->getButtons();
            // // click the continue button
            // $this->clickButtonsWithText($buttons[0], $buttons[1], ["Continue"]);

            sleep(rand(10, 15));
            if (count($this->driver->findElements(WebDriverBy::name("tfa"))) > 0) {
                $otp = $krakenService->getOTP();
                $otpInput = $this->driver->findElement(WebDriverBy::name("tfa"));
                $otpInput->sendKeys($otp);
                $otpInput->sendKeys(WebDriverKeys::ENTER);
            }

            // Execute JavaScript for scrolling
            $this->driver->executeScript("window.scrollTo(0,99.89418029785156)");
        } catch (\Exception $e) {
            sleep(5);
            $this->driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
            $source = $this->driver->getPageSource();
            $this->driver->quit();
            dd($source, $e);
        }
    }

    public function approveDevice(): \Illuminate\Http\JsonResponse
    {
        try {
            // set window size
            $this->driver->manage()->window()->setSize(new WebDriverDimension(1920, 1080));
            // wait until the page is loaded
            $this->driver->wait(10, 1000)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector(".my-px"))
            );
            //

            // we may need to click the button if email doesn't auto send
            // $this->driver->findElement(WebDriverBy::cssSelector(".my-px"))->click();

            // if above code stops working, use the code below
            // sleep(5);
            //
            // $buttons = $this->getButtons();
            // // click button with send email or resend email
            // $this->clickButtonsWithText($buttons[0], $buttons[1], ["Send email", "Resend email"]);

            $iterations = 0;
            $code = null;
            while ($code === null) {
                sleep(5);
                $code = $this->getLinkFromLastEmail();
                $iterations++;
                if ($iterations > 5) {
                    $discordService = new DiscordService();
                    $discordService->sendMessage('No link found in most recent email from Kraken.');
                    return response()->json(['error' => 'No link found in most recent email from Kraken.']);
                }
            }

            // resize the window to split the screen
            $this->driver->manage()->window()->setSize(new WebDriverDimension(960, 1080));
            $this->driver->get($code);

            sleep(2);

            return response()->json(['success' => 'Device approved']);
        } catch (\Exception $e) {
            $this->driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
            $source = $this->driver->getPageSource();
            $this->driver->quit();
            dd($source, $e);
        }
    }

    public function getLinkFromLastEmail($start = 'https://www.kraken.com/new-device-sign-in/web?code=')
    {

        // grab email
        $gmailService = new \App\Services\GmailService();
        $text = $gmailService->getLastEmail();

        $link = $gmailService->grabLink($text, $start);
        if ($link === null) {

            // $discordService = new \App\Services\DiscordService();
            // $discordService->sendMessage('No link found in most recent email from Kraken.');
            return null;

        }

        $this->linkUsed = $link;

        return $link;
    }

    // get cookies
    public function getCookies(): array
    {
        return $this->driver->manage()->getCookies();
    }

    // set cookies
    public function setCookies($cookies): void
    {
        foreach ($cookies as $cookie) {
            $this->driver->manage()->addCookie($cookie);
        }
    }

    public function getButtons()
    {
        // dump all buttons
        $buttons = $this->driver->findElements(WebDriverBy::tagName('button'));
        // foreach button grab the text inside it
        $buttonValues = [];
        foreach ($buttons as $button) {
            $saveSpans = [];
            $text = $button->getText();
            // check if the button has any spans inside it
            if (count($button->findElements(WebDriverBy::tagName('span'))) > 0) {
                $spans = $button->findElements(WebDriverBy::tagName('span'));
                foreach ($spans as $span) {
                    $saveSpans[] = $span->getText();
                }
            }
            $buttonValues[] = ['text' => $text, 'spans' => $saveSpans];
        }

        return [$buttons, $buttonValues];
    }

    public function clickButtonsWithText(mixed $buttons, mixed $buttonValues, array $texts): void
    {
        // try {
            foreach ($texts as $text) {
                // $indexes = array_search($text, array_column($buttonValues, 'text'));
                $indexes = array_keys(array_column($buttonValues, 'text'), $text);
                foreach ($indexes as $index) {
                    $webDriverBy = WebDriverBy::id($buttons[$index]->getAttribute('id'));
                    // check if button is clickable
                    if ($buttons[$index]->isEnabled() && $buttons[$index]->isDisplayed()
                        && WebDriverExpectedCondition::elementToBeClickable($webDriverBy)
                        && WebDriverExpectedCondition::visibilityOfElementLocated($webDriverBy)
                    ) {
                        $buttons[$index]->click();
                        sleep(1);
                        break;
                    }

                }
            }
        // } catch (\Exception $e) {
        //     $this->driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
        //     $source = $this->driver->getPageSource();
        //     $this->driver->quit();
        //     dd($source, $e, $buttons, $buttonValues);
        // }
    }

    // grab links from the page and the text inside them
    public function getLinks(): array
    {
        $links = $this->driver->findElements(WebDriverBy::tagName('a'));
        $linkValues = [];
        foreach ($links as $link) {
            $linkValues[] = ['text' => $link->getText(), 'href' => $link->getAttribute('href')];
        }

        return $linkValues;
    }

    // click links with text
    public function clickLinksWithText(mixed $links, array $texts): void
    {
        foreach ($texts as $text) {
            $indexes = array_keys(array_column($links, 'text'), $text);
            foreach ($indexes as $index) {
                $webDriverBy = WebDriverBy::id($links[$index]->getAttribute('id'));
                // check if link is clickable
                if ($links[$index]->isEnabled() && $links[$index]->isDisplayed()
                    && WebDriverExpectedCondition::elementToBeClickable($webDriverBy)
                    && WebDriverExpectedCondition::visibilityOfElementLocated($webDriverBy)
                ) {
                    $links[$index]->click();
                    sleep(1);
                    break;
                }
            }
        }
    }

}
