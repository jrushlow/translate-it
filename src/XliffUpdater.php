<?php

namespace TranslateIt;

use Symfony\Component\Translation\Dumper\XliffFileDumper;
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
    private XliffFileDumper $dumper;
    private MessageCatalogue $catalogue;

    /**
     * @var array<Translation>
     */
    private array $translations = [];

    public function __construct(XliffFileLoader $loader, Translator $aws, XliffFileDumper $fileDumper)
    {
        $this->loader = $loader;
        $this->aws = $aws;
        $this->dumper = $fileDumper;
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

        $this->catalogue = $this->loader->load($masterFilePath, $masterLocale, $masterDomain);

        $this->diff = new CatalogueDiff($this->catalogue);

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

        if (!empty($nullLocales)) {
            $originalMessages = $this->catalogue->all($masterDomain);

            foreach ($originalMessages as $key => $value) {
                $originalMessages[$key] = null;
            }

            $masterMetadata = $this->catalogue->getMetadata();

            foreach ($nullLocales as $locale) {
                $catalogue = new MessageCatalogue($locale, [$masterDomain => $originalMessages]);

                foreach ($masterMetadata as $key => $data) {
                    $catalogue->setMetadata($key, $data, $masterDomain);
                }

                $subjectCatalogues[] = $catalogue;
            }
        }

        $diffs = [];

        foreach ($subjectCatalogues as $subject) {
            $diffs = array_merge($diffs, $this->diff->getDiff($subject));
        }


        $translated = [];

        /** @var Message $message */
        foreach ($diffs as $message) {
            $response = $this->aws->translate($this->catalogue->get($message->getId()), $this->catalogue->getLocale(), $message->getLocal());

            $message->setMessage($response->get('TranslatedText'));

            $array = array_filter($subjectCatalogues, function ($c) use ($message) {
                return $message->getLocal() === $c->getLocale() && in_array(
                        $message->getDomain(),
                        ($c->getDomains()),
                        true
                    );
            });

            $key = array_key_first($array);
            $subjectToBeUpdated = $array[$key];
            $subjectToBeUpdated->set($message->getId(), $message->getMessage(), $message->getDomain());
            $subjectToBeUpdated->setMetadata($message->getId(), ['source' => $message->getId(), 'id' => $message->getId()], $message->getDomain());

            $subjectCatalogues[$key]->addCatalogue($subjectToBeUpdated);
            $translated[] = $message;
        }

        $xlf = [];

        foreach ($subjectCatalogues as $catalogue) {
            $xlf[] = $this->dumper->formatCatalogue($catalogue, $masterDomain, ['xliff_version' => '1.2', 'default_locale' => $masterLocale]);
            $this->dumper->dump($catalogue, ['path' => '/var/htdocs/translations/gen', 'xliff_version' => '1.2', 'default_locale' => $masterLocale]);
        }

        return $translated;
    }

    private function getXlfPath(string $dir, string $domain, string $locale): string
    {
        return sprintf('%s/%s+intl-icu.%s.xlf', $dir, $domain, $locale);
    }
}
