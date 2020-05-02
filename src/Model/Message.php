<?php

namespace TranslateIt\Model;

class Message
{
    private string $locale;
    private string $source;
    private string $id;
    private string $message;
    private string $domain;

    public function __construct(string $locale, string $source = '', string $id = '', string $message = '', string $domain = '')
    {
        $this->locale = $locale;
        $this->source = $source;
        $this->id = $id;
        $this->message = $message;
        $this->domain = $domain;
    }

    public function getLocal(): string
    {
        return $this->locale;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }
}
