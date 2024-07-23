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


            // Set window size
            $this->driver->manage()->window()->setSize(new WebDriverDimension(1085, 575));

            // wait until the page is loaded
            $this->driver->wait(10, 1000)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id(":r9:"))
            );

            // Perform the actions from the JUnit code
            // $this->driver->findElement(WebDriverBy::cssSelector('.ml-4 > .inline-block > .rounded-ds-round'))->click();
            $this->driver->findElement(WebDriverBy::id(":r9:"))->click();
            $this->driver->findElement(WebDriverBy::id(":r9:"))->sendKeys(env('KRAKEN_USERNAME'));
            $this->driver->findElement(WebDriverBy::id(":ra:"))->sendKeys(env('KRAKEN_PASSWORD'));
            $this->driver->findElement(WebDriverBy::cssSelector(".absolute"))->click();
            sleep(2);
            if (count($this->driver->findElements(WebDriverBy::id(":rb:"))) > 0) {
                $this->driver->wait(10, 1000)->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id(":rb:"))
                );
                $otp = $krakenService->getOTP();
                $this->driver->findElement(WebDriverBy::id(":rb:"))->sendKeys($otp);
                $this->driver->findElement(WebDriverBy::id(":rb:"))->sendKeys(WebDriverKeys::ENTER);
            }

            // Execute JavaScript for scrolling
            $this->driver->executeScript("window.scrollTo(0,99.89418029785156)");
        } catch (\Exception $e) {
            sleep(5);
            $this->driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
            $source = $this->driver->getPageSource();
            $this->driver->quit();
            dd($source);
        }
    }

    public function approveDevice(): \Illuminate\Http\JsonResponse
    {
        try {
            // set window size
            $this->driver->manage()->window()->setSize(new WebDriverDimension(1085, 575));
            // wait until the page is loaded
            $this->driver->wait(10, 1000)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector(".my-px"))
            );

            // click the button
            $this->driver->findElement(WebDriverBy::cssSelector(".my-px"))->click();

            sleep(30);

            // grab email
            $gmailService = new \App\Services\GmailService();
            $text = $gmailService->getLastEmail();

            $link = $gmailService->grabLink($text);
            if ($link === null) {
                // close the driver
                $this->driver->quit();
                return response()->json(['error' => 'No link found']);
            }

            // go to the link with same session
            $this->driver->get($link);
            $this->linkUsed = $link;

            sleep(2);

            return response()->json(['success' => 'Device approved']);
        } catch (\Exception $e) {
            $this->driver->takeScreenshot('temp-' . Carbon::now()->toDateTimeString() . '.png');
            $source = $this->driver->getPageSource();
            $this->driver->quit();
            dd($source);
        }
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
        $count = 0;
        foreach ($texts as $text) {
            $index = array_search($text, array_column($buttonValues, 'text'));
            if ($index !== false) {
                $count++;

                // check if button is clickable
                if ($buttons[$index]->isEnabled() && $buttons[$index]->isDisplayed()) {
                    $buttons[$index]->click();
                    sleep(1);
                }

            }
        }
    }
}
