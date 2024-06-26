<?php

namespace App\WorkerClasses;

class Robosats
{

    public function apiBook() {
        $url = 'https://api.robosats.com/api/v1/book';
        $headers = [
            "Host: 192.168.0.18:12596",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0",
            "Accept: */*",
            "Accept-Language: en-GB,en;q=0.5",
            "Accept-Encoding: gzip, deflate",
            "Referer: http://192.168.0.18:12596/",
            "Content-Type: application/json",
            "Connection: keep-alive",
            "Cookie: UMBREL_PROXY_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwcm94eVRva2VuIjp0cnVlLCJpYXQiOjE3MTk0MzI5MzQsImV4cCI6MTcyMDAzNzczNH0.31qKPyd1zRoySVRPVzisbTxO_FljIisBOHJFyJs6JYc",
            "Priority: u=4",
            "Pragma: no-cache",
            "Cache-Control: no-cache"
        ];

        // make get request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        // return response
        return $output;



    }


}
