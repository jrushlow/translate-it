<?php

namespace TranslateIt\Util;

use Symfony\Component\Translation\MessageCatalogue;
use TranslateIt\Model\Message;

class CatalogueDiff
{
    private MessageCatalogue $catalogue;
    private array $domains;

    public function __construct(MessageCatalogue $catalogue)
    {
        $this->catalogue = $catalogue;
        $this->domains = $catalogue->getDomains();
    }

    public function getDiff(MessageCatalogue $catalogue): array
    {
        $uniqueMessages = [];

        $uniqueMasterDomains = array_diff($this->domains, $catalogue->getDomains());

        if (!empty($uniqueMasterDomains)) {
            foreach ($uniqueMasterDomains as $domain) {
                $uniqueDomainMessages = $this->catalogue->all($domain);

                foreach ($uniqueDomainMessages as $uniqueMessage) {
                    //@TODO add domain messages to uniqueMessages
                }
            }
        }

        foreach ($this->domains as $domain) {
            if (array_key_exists($domain, $uniqueMasterDomains)) {
                continue;
            }

            $messages = $this->catalogue->all($domain);

            foreach ($messages as $id => $message) {
                if (!$catalogue->defines($id, $domain)) {
                    $uniqueMessages[] = new Message($catalogue->getLocale(), $id, $id, '', $domain);
                }
            }
        }

        return $uniqueMessages;
    }
}
