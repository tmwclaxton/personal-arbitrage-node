<?php

use HeadlessChromium\BrowserFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $url = 'http://192.168.0.18:12596/mainnet/veneto/api/book/';
    $headers = [
        "Host" => "192.168.0.18:12596",
        "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0",
        "Accept" => "*/*",
        "Accept-Language" => "en-GB,en;q=0.5",
        "Accept-Encoding" => "gzip, deflate",
        "Referer" => "http://192.168.0.18:12596/",
        "Content-Type" => "application/json",
        "Connection" => "keep-alive",
        "Cookie" => "UMBREL_PROXY_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwcm94eVRva2VuIjp0cnVlLCJpYXQiOjE3MTk0MzI5MzQsImV4cCI6MTcyMDAzNzczNH0.31qKPyd1zRoySVRPVzisbTxO_FljIisBOHJFyJs6JYc",
        "Priority" => "u=4",
        "Pragma" => "no-cache",
        "Cache-Control" => "no-cache"
    ];

    // make get request
    $response = Http::withHeaders($headers)->get($url);

    return $response->body();
});
