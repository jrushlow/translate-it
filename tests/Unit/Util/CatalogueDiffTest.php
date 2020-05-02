<?php

namespace TranslateIt\Tests\Unit\Util;

use Symfony\Component\Translation\MessageCatalogue;
use TranslateIt\Model\Message;
use TranslateIt\Util\CatalogueDiff;
use PHPUnit\Framework\TestCase;

class CatalogueDiffTest extends TestCase
{
    public function test(): void
    {
        $master = new MessageCatalogue('en');
        $master->add(['note' => 'NOTE', 'time' => 'TIME', 'test' => 'TEST']);

        $slave = new MessageCatalogue('es');
        $slave->add(['note' => 'NOTA', 'test' => 'PRUEBA']);

        $expected = [(new Message('es', 'time', 'time', 'TIME', 'messages'))];

        $catalogueDiff = new CatalogueDiff($master);
        $result = $catalogueDiff->getDiff($slave);

        self::assertCount(1, $result);
        self::assertEquals($expected, $result);
    }
}
