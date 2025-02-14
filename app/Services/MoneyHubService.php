<?php

namespace App\Services;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Illuminate\Http\Request;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class MoneyHubService
{
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

        // Create a DesiredCapabilities object for Chrome
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        // URL of your Selenium container (running on Docker)
        $selenium_url = "http://selenium:4444/wd/hub";  // This connects to the Docker container

//        dd("hi1");
        // Connect to the remote Selenium WebDriver
        $driver = RemoteWebDriver::create($selenium_url, $capabilities);
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
//        $driver->wait(10)->until(
//            WebDriverExpectedCondition::visibilityOfElementLocated(
//                WebDriverBy::xpath("//h1[text()='Dashboard']")
//            )
//        );

        $html = $driver->getPageSource();
        $title = $driver->getTitle();


        // Output the HTML
        echo $html;


        // Close the browser
        $driver->quit();

        return $title;
    }
}
