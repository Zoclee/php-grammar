<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarInput;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Php\Lexing\TokenType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php85HookNameTest extends TestCase
{
    #[DataProvider('names')]
    public function testOnlySupportedNamesMatch(string $name, bool $expected): void
    {
        $matcher = PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3));
        self::assertSame($expected, $matcher->matchesRule('8.5', 'hook-name', $name)->matched);
        self::assertSame($expected, $matcher->matches('8.5', '<?php class C { public $x { ' . $name . ' => $this->x; } }')->matched);
    }

    public static function names(): iterable
    {
        foreach (['get', 'set'] as $name) {
            for ($mask = 0; $mask < 8; $mask++) {
                $variant = $name;
                for ($i = 0; $i < 3; $i++) {
                    if ($mask & (1 << $i)) $variant[$i] = strtoupper($variant[$i]);
                }
                yield $variant => [$variant, true];
            }
        }
        foreach (['unknown', 'getter', 'setter', 'getValue', 'set_value', 'get1', '\\get', 'Ns\\set', '$get', '"get"', 'get set'] as $name) {
            yield $name => [$name, false];
        }
    }

    public function testNormalizationPreservesIdentifierTokensAndOtherUses(): void
    {
        $input = new PhpGrammarInput(Lexer::forPhp85()->tokenize('<?php GET; SeT;')->withoutTrivia());
        self::assertSame('get', $input->valueAt(1));
        self::assertSame('set', $input->valueAt(3));
        self::assertSame('GET', $input->tokenAt(1)->lexeme);
        self::assertSame('SeT', $input->tokenAt(3)->lexeme);
        self::assertSame(TokenType::Identifier, $input->tokenAt(1)->type);
        $matcher = PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3));
        self::assertTrue($matcher->matchesRule('8.5', 'identifier', 'GET')->matched);
        self::assertTrue($matcher->matches('8.5', '<?php function GET() {} class Set { public $GET; function set() {} } GET(); $x->GET;')->matched);
    }

    public function testDiscardedUnknownHookIsAnExplicitGrammarDifference(): void
    {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/tests/fixtures/php/8.5/known-discrepancies/valid/boundary-hook-name-discarded.php');
        self::assertFalse(PhpGrammarMatcher::forRepositoryRoot($root)->matches('8.5', $source)->matched);
    }
}
