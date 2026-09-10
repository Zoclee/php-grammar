<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Ebnf\Coverage\CoverageCollector;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php85LexicalPrimitiveTest extends TestCase
{
    #[DataProvider('primitives')]
    public function testPrimitiveRecognizesWholeTokenWithoutTraversingItsEbnfBody(string $rule, string $source): void
    {
        $coverage = new CoverageCollector();
        $result = PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3))->withCoverage($coverage)
            ->matchesRule('8.5', $rule, $source);
        self::assertTrue($result->matched, $rule . ': ' . $source);
        self::assertContains($rule, $coverage->matchedPrimitives());
        self::assertNotContains($rule, $coverage->matchedProductions());
    }

    public static function primitives(): iterable
    {
        $matrix = [
            'identifier' => ['example', '_x', 'café'],
            'name-identifier' => ['example'],
            'semi-reserved-identifier' => ['match', 'readonly', '__PROPERTY__'],
            'variable' => ['$x'],
            'qualified-name' => ['A\\B'],
            'fully-qualified-name' => ['\\A\\B'],
            'namespace-relative-name' => ['namespace\\f'],
            'decimal-integer-literal' => ['0', '12_345'],
            'binary-integer-literal' => ['0b10_01', '0B11'],
            'octal-integer-literal' => ['0_755'],
            'explicit-octal-integer-literal' => ['0o7_55', '0O755'],
            'hexadecimal-integer-literal' => ['0xCA_FE', '0Xff'],
            'floating-literal' => ['1.', '.5', '1E+3', '1.2e-3'],
            'single-quoted-string' => ["'x'", "B'x'"],
            'double-quoted-string' => ['"$x"', 'b"x"'],
            'constant-double-quoted-string' => ['"x"'],
            'constant-string-literal' => ["<<<TXT\nx\nTXT", "<<<'TXT'\nx\nTXT"],
            'string-literal' => ['"{$x}"'],
            'heredoc-string' => ["<<<TXT\n\$x\nTXT"],
            'nowdoc-string' => ["<<<'TXT'\n\$x\nTXT"],
            'backtick-string' => ['`echo $x`'],
        ];
        foreach ($matrix as $rule => $sources) {
            foreach ($sources as $index => $source) yield $rule . ' #' . $index => [$rule, $source];
        }
    }
}
