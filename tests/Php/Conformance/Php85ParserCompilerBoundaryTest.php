<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PHPUnit\Framework\TestCase;

final class Php85ParserCompilerBoundaryTest extends TestCase
{
    public function testEveryBoundaryHasLiveRetainedAndDiscardedWitnesses(): void
    {
        $root = dirname(__DIR__, 3) . '/tests/fixtures/php/8.5/';
        $data = json_decode(file_get_contents($root . 'parser-compiler-boundaries.json'), true, flags: JSON_THROW_ON_ERROR);
        $ids = [];
        foreach ($data['cases'] as $case) {
            self::assertArrayNotHasKey($case['id'], $ids);
            $ids[$case['id']] = true;
            self::assertNotEmpty($case['evidence']);
            $source = $case['source'];
            $forms = ['live' => $source,
                'retained' => "const X = static function() { $source };",
                'discarded' => "const X = true ? 1 : static function() { $source };"];
            foreach ($forms as $form => $expected) {
                $category = $form === 'discarded' || $case['live_valid'] ? 'valid' : 'contextual-invalid';
                self::assertStringStartsWith($category . '/', $case['fixtures'][$form]);
                self::assertSame("<?php\n$expected\n", str_replace("\r\n", "\n", file_get_contents($root . $case['fixtures'][$form])));
            }
        }
    }
}
