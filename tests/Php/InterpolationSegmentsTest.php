<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php;

use PhpGrammar\Tests\Support\InterpolationSegments;
use PHPUnit\Framework\TestCase;

final class InterpolationSegmentsTest extends TestCase
{
    public function testDeprecatedVarnameAndVariableVariableKeepDifferentBindings(): void
    {
        self::assertSame([
            ['expression', '$name[0]'], ['text', ' / '],
            ['expression', '${$a + $b * 2}'],
        ], InterpolationSegments::split('${name[0]} / ${$a + $b * 2}'));
    }

    public function testSimplePropertyStopsBeforeFollowingLiteralAndComplexBracesStayNested(): void
    {
        self::assertSame([
            ['expression', '$a->p'], ['text', '->q '], ['expression', '$a->{$b + $c * 2}'],
        ], InterpolationSegments::split('$a->p->q {$a->{$b + $c * 2}}'));
    }

    public function testAuditDoesNotSilentlyAcceptUnsupportedEscapes(): void
    {
        $this->expectException(\LogicException::class);
        InterpolationSegments::split('text\\n$a');
    }
}
