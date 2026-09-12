<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Lexing;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Lexing\{Lexer, LexerException, LexerState, TokenType};
use PhpGrammar\Tests\Support\{Php85LexicalAudit, Php85LexicalCases};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php85ScannerAuditTest extends TestCase
{
    public static function cases(): iterable
    {
        foreach (Php85LexicalCases::all() as $id => $case) yield $id => [$case];
    }

    #[DataProvider('cases')]
    public function testIndependentTokenExpectations(array $case): void
    {
        if (isset($case['error'])) {
            $this->expectException(LexerException::class);
            $this->expectExceptionMessage($case['error']);
        }
        $tokens = Lexer::forPhp85()->withShortOpenTag($case['short'])->tokenize($case['source'])->all();
        self::assertSame($case['tokens'], array_map(static fn ($t) => [$t->type->value, $t->lexeme], $tokens));
        $offset = 0;
        foreach ($tokens as $token) {
            self::assertSame($offset, $token->offset);
            self::assertSame(strlen($token->lexeme), $token->length);
            $prefix = substr($case['source'], 0, $offset);
            // CRLF is one line break, even when its bytes belong to different tokens.
            preg_match_all('/\r\n|\r|\n/', $prefix, $lines, PREG_OFFSET_CAPTURE);
            $last = $lines[0] === [] ? null : $lines[0][array_key_last($lines[0])];
            self::assertSame(count($lines[0]) + 1, $token->line);
            self::assertSame($last === null ? $offset + 1 : $offset - $last[1] - strlen($last[0]) + 1, $token->column);
            $offset += $token->length;
        }
        self::assertSame(strlen($case['source']), $offset);
    }

    public static function primitives(): iterable
    {
        foreach (Php85LexicalCases::primitives() as $family => [$rule, $valid, $invalid]) {
            foreach ([1 => $valid, 0 => $invalid] as $accepted => $sources) {
                foreach ($sources as $index => $source) yield "$family/$accepted/$index" => [$rule, $source, (bool) $accepted];
            }
        }
    }

    #[DataProvider('primitives')]
    public function testIndependentPrimitiveExpectations(string $rule, string $source, bool $expected): void
    {
        self::assertSame($expected, PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3))->matchesRule('8.5', $rule, $source)->matched);
    }

    public function testStateDoesNotLeakAcrossCallsOrErrors(): void
    {
        $lexer = Lexer::forPhp85();
        foreach (['<?php "unterminated', '<?php /*', "<?php <<<X\nx"] as $source) {
            try { $lexer->tokenize($source); self::fail('Expected lexical failure'); } catch (LexerException) {}
            self::assertSame(TokenType::InlineHtml, $lexer->tokenize('text')->all()[0]->type);
            self::assertSame(TokenType::Keyword, $lexer->tokenize('<?php class')->all()[2]->type);
        }
    }

    public function testCrLfSplitBetweenConsumptionsIsOneLineBreak(): void
    {
        $state = new LexerState("\r\nX");
        $state->consume(1, TokenType::Whitespace);
        $state->consume(1, TokenType::Whitespace);
        $token = $state->consume(1, TokenType::Identifier);
        self::assertSame([2, 1, 2, 1], [$token->line, $token->column, $token->offset, $token->length]);
        $state = new LexerState("\nX\r");
        $state->consume(1, TokenType::Whitespace);
        self::assertSame(2, $state->consume(1, TokenType::Identifier)->line, 'Initial LF must not read the final CR through PHP negative indexing.');
    }

    public function testLedgerIsFreshAndEveryRuleAndBypassedIdentityHasEvidence(): void
    {
        $root = dirname(__DIR__, 3);
        $data = Php85LexicalAudit::report($root);
        self::assertSame(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
            file_get_contents($root . '/docs/8.5/phase5-lexical-evidence.json'));
        self::assertCount(190, $data['rules']);
        $counts = [];
        foreach ($data['grammar_bypasses'] as $entry) {
            $key = $entry['kind'] . ':' . $entry['classification'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            self::assertArrayHasKey($entry['family'], $data['families']);
        }
        self::assertSame(52, $counts['production:primitive-bypassed']);
        self::assertSame(174, $counts['alternative:primitive-bypassed']);
        self::assertSame(11, $counts['production:trivia-removed']);
        self::assertSame(9, $counts['alternative:trivia-removed']);
        self::assertCount(1, $data['contextual_only']);
        self::assertCount(1, $data['scanner_context_only']);
        foreach ($data['families'] as $family) {
            self::assertNotEmpty($family['positive']);
            self::assertNotEmpty($family['boundary']);
            self::assertNotEmpty($family['implementation']);
        }
    }

    public function testRawCodeUnitAndSourceOffsetsAreBytes(): void
    {
        $grammar = (new \PhpGrammar\Ebnf\Parser())->parse('source-file = code-unit;');
        $matcher = \PhpGrammar\Ebnf\Matching\Matcher::withDefaultPrimitives();
        for ($byte = 0; $byte < 256; $byte++) self::assertTrue($matcher->matches($grammar, chr($byte))->matched);
        self::assertFalse($matcher->matches($grammar, "\xc3\xa9")->matched);
        self::assertFalse($matcher->matches($grammar, '')->matched);
    }

    public static function syntax(): iterable
    {
        yield from Php85LexicalCases::syntax();
    }

    #[DataProvider('syntax')]
    public function testScannerAndPrimitiveIntegration(string $source, bool $accepted): void
    {
        self::assertSame($accepted, PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3))->matches('8.5', $source)->matched);
    }
}
