<?php

namespace TranslateIt\Aws;

use Aws\ResultInterface;
use Aws\Translate\TranslateClient;

class Translator
{
    private TranslateClient $client;

    public function __construct(TranslateClient $client)
    {
        $this->client = $client;
    }

    public function translate(string $message, string $sourceLang, string $targetLang): ResultInterface
    {
        return $this->client->translateText(['Text' => $message, 'SourceLanguageCode' => $sourceLang, 'TargetLanguageCode' => $targetLang]);
    }
}
