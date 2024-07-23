<?php
//
// namespace App\Services;
//
// use GuzzleHttp\Client;
// use GuzzleHttp\Exception\RequestException;
//
// class KrakenAPIService
// {
//     private $apiUrl = "https://api.kraken.com";
//     private $apiKey;
//     private $apiSec;
//     private $client;
//
//     public function __construct()
//     {
//         $this->apiKey =  env('KRAKEN_API_KEY');
//         $this->apiSec = env('KRAKEN_PRIVATE_KEY');
//         $this->client = new Client();
//     }
//
//     private function getKrakenSignature($path, $data): string
//     {
//         $postdata = http_build_query($data, '', '&');
//         $nonce = $data['nonce'];
//         $message = $nonce . $postdata;
//         $hash = hash_hmac('sha512', $path . hash('sha256', $nonce . $message, true), base64_decode($this->apiSec), true);
//         return base64_encode($hash);
//     }
//
//     function get_kraken_signature($uri_path, $data) {
//         $postdata = http_build_query($data, '', '&');
//         $nonce = $data['nonce'];
//         $message = $nonce . $postdata;
//         $path = '/0/private/' . $uri_path;
//         $hash = hash_hmac('sha256', $nonce . $postdata, base64_decode($this->apiSec), true);
//         $signature = base64_encode(
//             hash_hmac('sha512', $path . hash('sha256', $nonce . $postdata, true),
//                 base64_decode($this->apiSec), true));
//         return $signature;
//     }
//
//     // Function to make a Kraken API request
//     function kraken_request($uri_path, $data) {
//         $api_url = 'https://api.kraken.com';
//         $nonce = explode(' ', microtime());
//         $nonce = $nonce[1] . str_pad(substr($nonce[0], 2, 6), 6, '0');
//
//         $data['nonce'] = $nonce;
//
//         // $headers = array(
//         //     'API-Key: ' . env('KRAKEN_API_KEY'),
//         //     'API-Sign: ' . $this->get_kraken_signature($uri_path, $data),
//         //     'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
//         // );
//
//         $headers = [
//             'API-Key: ' . env('KRAKEN_API_KEY'),
//             'API-Sign: ' . $this->get_kraken_signature($uri_path, $data),
//             'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
//         ];
//         // dd($headers, $data);
//         //curl -X "POST" "https://api.kraken.com/0/private/WithdrawMethods" \
//         //      -H 'API-Key: <YOUR-API-KEY>' \
//         //      -H 'API-Sign: <YOUR-MSG-SIGNATURE>' \
//         //      -H 'Content-Type: application/x-www-form-urlencoded; charset=utf-8' \
//         //      --data-urlencode "nonce=<YOUR-NONCE>" \
//         //      --data-urlencode "asset=XBT"
//
//         $curlManualRequest = "";
//         $curlManualRequest .= "curl -X \"POST\" \"https://api.kraken.com/0/private/WithdrawMethods\" ";
//         $curlManualRequest .= "      -H 'API-Key: " . env('KRAKEN_API_KEY') . "'";
//         $curlManualRequest .= "      -H 'API-Sign: " . $this->get_kraken_signature($uri_path, $data) . "'";
//         $curlManualRequest .= "      -H 'Content-Type: application/x-www-form-urlencoded; charset=utf-8'";
//         $curlManualRequest .= "      --data-urlencode \"nonce=" . $nonce . "\"";
//         $curlManualRequest .= "      --data-urlencode \"asset=XBT\"";
//         dd($curlManualRequest);
//
//
//         $ch = curl_init($api_url . $uri_path);
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_POST, true);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
//         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//
//         $response = curl_exec($ch);
//         curl_close($ch);
//
//         return $response;
//     }
// }
//
