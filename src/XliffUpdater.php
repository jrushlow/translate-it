<?php

namespace TranslateIt;

use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\MessageCatalogue;
use TranslateIt\Aws\Translator;
use TranslateIt\Model\Message;
use TranslateIt\Model\Translation;
use TranslateIt\Util\CatalogueDiff;

class XliffUpdater
{
    private XliffFileLoader $loader;
    private Translator $aws;
    private CatalogueDiff $diff;
    private MessageCatalogue $catalogue;

    /**
     * @var array<Translation>
     */
    private array $translations = [];

    public function __construct(XliffFileLoader $loader, Translator $aws)
    {
        $this->loader = $loader;
        $this->aws = $aws;
    }

    public function updateTranslations(string $dir, string $masterDomain, string $masterLocale, array $locales)
    {
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \RuntimeException(sprintf('%s is not a dir or writable.', $dir));
        }

        $masterFilePath = $this->getXlfPath($dir, $masterDomain, $masterLocale);

        if (!is_readable($masterFilePath)) {
            throw new \RuntimeException(sprintf('Cannot read %s', $masterFilePath));
        }

        $masterCatalogue = $this->loader->load($masterFilePath, $masterLocale, $masterDomain);

        $this->diff = new CatalogueDiff($masterCatalogue);

        $subjectCatalogues = [];
        $nullLocales = [];

        foreach ($locales as $locale) {
            $xlf = $this->getXlfPath($dir, $masterDomain, $locale);

            if (!file_exists($xlf)) {
                $nullLocales[] = $locale;

                continue;
            }

            if (!is_writable($xlf)) {
                throw new \RuntimeException(sprintf('%s is not writable', $xlf));
            }

            $subjectCatalogues[] = $this->loader->load($xlf, $locale, $masterDomain);
        }

        $diffs = [];

        foreach ($subjectCatalogues as $subject) {
            $diffs = array_merge($diffs, $this->diff->getDiff($subject));
        }


        $translated = [];

        /** @var Message $message */
        foreach ($diffs as $message) {
            $response = $this->aws->translate($masterCatalogue->get($message->getId()), $masterCatalogue->getLocale(), $message->getLocal());

            $message->setMessage($response->get('TranslatedText'));

            $translated[] = $message;
        }

        return $translated;
    }

    private function getXlfPath(string $dir, string $domain, string $locale): string
    {
        return sprintf('%s/%s+intl-icu.%s.xlf', $dir, $domain, $locale);
    }
}
