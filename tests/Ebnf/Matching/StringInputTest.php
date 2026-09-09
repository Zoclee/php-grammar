<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf\Matching;

use OutOfBoundsException;
use PhpGrammar\Ebnf\Matching\StringInput;
use PHPUnit\Framework\TestCase;

final class StringInputTest extends TestCase
{
    public function testExposesStringAsCodeUnits(): void
    {
        $input = new StringInput('abc');

        self::assertSame(3, $input->length());
        self::assertSame('a', $input->valueAt(0));
        self::assertSame('b', $input->valueAt(1));
        self::assertSame('c', $input->valueAt(2));
    }

    public function testCodeUnitsAreRawBytesForStringInput(): void
    {
        $input = new StringInput('é');

        self::assertSame(2, $input->length());
        self::assertSame("\xC3", $input->valueAt(0));
        self::assertSame("\xA9", $input->valueAt(1));
    }

    public function testRejectsOutOfBoundsOffset(): void
    {
        $this->expectException(OutOfBoundsException::class);

        (new StringInput('a'))->valueAt(1);
    }
}
