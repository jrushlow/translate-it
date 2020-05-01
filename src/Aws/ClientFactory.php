<?php

namespace TranslateIt\Aws;

use Aws\Credentials\Credentials;
use Aws\Handler\GuzzleV6\GuzzleHandler;
use Aws\Translate\TranslateClient;
use GuzzleHttp\Client;

class ClientFactory
{
    private string $key;
    private string $secret;

    public function __construct(string $key, string $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    public function getClient(): TranslateClient
    {
        $credentials = new Credentials($this->key, $this->secret);

        $handler = new GuzzleHandler(new Client([
            'curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]
        ]));

        return new TranslateClient([
            'region' => 'us-east-2',
            'version' => 'latest',
            'http_handler' => $handler,
            'credentials' => $credentials,
        ]);
    }
}
