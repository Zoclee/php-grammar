<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Conformance\VersionBoundaryFixtureRepository;
use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php86PartialApplicationTest extends TestCase
{
    private static function root(): string { return dirname(__DIR__, 3); }

    /** Test-local configuration only: no public 8.6 discovery or lexer registration.
     * The unchanged 8.5 token adapter is used for this bounded PFA corpus.
     */
    private static function matcher(): PhpGrammarMatcher
    {
        $data = json_decode(file_get_contents(self::root() . '/php-grammar.json'), true, 512, JSON_THROW_ON_ERROR);
        $data['versions'][] = [
            'version' => '8.6', 'rootProduction' => 'source-file',
            'grammar' => 'grammar/8.6/php.ebnf', 'documentation' => 'grammar/8.6/php.md',
            'conformanceFixtures' => 'tests/fixtures/php/8.6', 'lexer' => '8.5',
            'status' => 'draft', 'sourceProfile' => 'PFA structural test corpus only; shared token adapter.',
            'phpSource' => ['branch' => 'PHP-8.6', 'commit' => 'f23b4c222cebe33be93ee8ecaaa14390c5507a08'],
        ];
        return PhpGrammarMatcher::forManifest(RepositoryManifest::fromArray(self::root(), $data));
    }

    #[DataProvider('cases')]
    public function testStructuralBoundaries(array $case): void
    {
        $source = file_get_contents(self::root() . '/' . $case['fixture']);
        foreach (['8.5', '8.6'] as $version) {
            $result = self::matcher()->matches($version, $source);
            self::assertSame($case['structural' . str_replace('.', '', $version)], $result->matched,
                $version . ': ' . $case['id'] . ': ' . implode(', ', $result->expected));
        }
    }

    public static function cases(): iterable
    {
        foreach (json_decode(file_get_contents(self::root() . '/tests/fixtures/php/8.6/pfa-cases.json'), true, 512, JSON_THROW_ON_ERROR) as $case) {
            yield $case['id'] => [$case];
        }
    }

    public function testOnlyMappedProductionsChangeAndDocumentationMatches(): void
    {
        $parser = new Parser();
        $old = $parser->parse(file_get_contents(self::root() . '/grammar/8.5/php.ebnf'));
        $source = file_get_contents(self::root() . '/grammar/8.6/php.ebnf');
        $new = $parser->parse($source);
        self::assertSame($old->productionNames(), $new->productionNames());
        $changed = [];
        foreach ($old->productions() as $production) {
            if (serialize($production->expression) !== serialize($new->production($production->name)->expression)) {
                $changed[] = $production->name;
            }
        }
        self::assertSame(['ordinary-argument-list', 'first-class-callable-arguments',
            'clone-argument-list', 'argument', 'argument-no-expression'], $changed);
        $doc = file_get_contents(self::root() . '/grammar/8.6/php.md');
        self::assertSame(1, preg_match('/<!-- BEGIN GENERATED EBNF -->\R```ebnf\R(.*?)\R```\R<!-- END GENERATED EBNF -->/s', $doc, $match));
        self::assertSame(trim($source), trim($match[1]));
    }

    public function testCallableAndOtherListsRemainDisjoint(): void
    {
        foreach (['(...)', '(...,)'] as $list) {
            self::assertTrue(self::matcher()->matchesRule('8.6', 'first-class-callable-arguments', $list)->matched);
            self::assertFalse(self::matcher()->matchesRule('8.6', 'ordinary-argument-list', $list)->matched);
        }
        foreach (['()', '(?)', '(a: ?)', '(1, ...)', '(..., ?)', '(...$args)'] as $list) {
            self::assertTrue(self::matcher()->matchesRule('8.6', 'ordinary-argument-list', $list)->matched, $list);
            self::assertFalse(self::matcher()->matchesRule('8.6', 'first-class-callable-arguments', $list)->matched, $list);
        }
        self::assertFalse(self::matcher()->matchesRule('8.6', 'clone-argument-list', '($object)')->matched);
        self::assertTrue(self::matcher()->matchesRule('8.6', 'clone-argument-list', '($object,)')->matched);
    }

    public function testExistingTokensDistinguishPlaceholderAndUnpacking(): void
    {
        $tokens = Lexer::forVersion('8.5')->tokenize('<?php f(? /* boundary */, ..., ...$args, a: ?);')->withoutTrivia()->all();
        $lexemes = array_map(static fn($token) => $token->lexeme, $tokens);
        self::assertSame(['<?php', 'f', '(', '?', ',', '...', ',', '...', '$args', ',', 'a', ':', '?', ')', ';'], $lexemes);
        $coalesce = Lexer::forVersion('8.5')->tokenize('<?php f(??);')->withoutTrivia()->all();
        self::assertContains('??', array_map(static fn($token) => $token->lexeme, $coalesce));
    }

    public function testVersionBoundaryRecordsAgainstBothGrammars(): void
    {
        $cases = (new VersionBoundaryFixtureRepository(self::root() . '/tests/fixtures/version-boundaries/8.6'))->load();
        self::assertCount(6, $cases);
        foreach ($cases as $case) {
            foreach (['8.5', '8.6'] as $version) {
                self::assertSame($case->shouldAccept($version), self::matcher()->matches($version, $case->source)->matched, $case->name);
            }
        }
    }
}
