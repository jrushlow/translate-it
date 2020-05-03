<?php

namespace TranslateIt\Tests\Unit;

use Aws\Result;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use TranslateIt\Aws\Translator;
use TranslateIt\Model\Message;
use TranslateIt\Util\CatalogueDiff;
use TranslateIt\XliffUpdater;
use PHPUnit\Framework\TestCase;

class XliffUpdaterTest extends TestCase
{
    public function testUpdated(): void
    {
        $awsResult = new Result(['TranslatedText' => 'No disponible']);

        $mockTranslator = $this->createMock(Translator::class);
        $mockTranslator
            ->expects($this->once())
            ->method('translate')
            ->with('Not available', 'en', 'es')
            ->willReturn($awsResult)
        ;

        $updater = new XliffUpdater(new XliffFileLoader(), $mockTranslator);

        $fixturesPath = dirname(__DIR__).'/fixtures';

        $result = $updater->updateTranslations($fixturesPath, 'messages', 'en', ['es', 'fr']);

        self::assertInstanceOf(Message::class, $result[0]);
        self::assertSame('No disponible', $result[0]->getMessage());
    }
}
