<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

use PhpGrammar\Ebnf\Coverage\CoverageCollector;
use PhpGrammar\Ebnf\Matching\Input;
use PhpGrammar\Ebnf\Matching\Matcher;
use PhpGrammar\Ebnf\Matching\MatchResult;
use PhpGrammar\Ebnf\Matching\StringInput;
use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Php\Lexing\LexerException;
use PhpGrammar\Php\Lexing\TokenStream;
use PhpGrammar\Php\Lexing\TokenType;
use PhpGrammar\Repository\RepositoryManifest;

final readonly class PhpGrammarMatcher
{
    public function __construct(
        private GrammarRepository $grammars,
        private ?CoverageCollector $coverage = null,
    ) {
    }

    public static function forRepositoryRoot(string $repositoryRoot): self
    {
        return new self(new GrammarRepository($repositoryRoot));
    }

    public static function forManifest(RepositoryManifest $manifest): self
    {
        return new self(new GrammarRepository($manifest));
    }

    public function withCoverage(CoverageCollector $coverage): self
    {
        return new self($this->grammars, $coverage);
    }

    public function matches(string $version, string $source): MatchResult
    {
        return $this->matchesRule($version, 'source-file', $source);
    }

    public function matchesRule(string $version, string $rule, string $source): MatchResult
    {
        $grammar = $this->grammars->load($version);
        $lexer = $this->lexerFor($version);
        try {
            $tokens = $this->tokenizeForRule($lexer, $rule, $source);
        } catch (LexerException $exception) {
            return new MatchResult(false, $rule, new StringInput($source), 0, [$exception->getMessage()]);
        }

        $input = new PhpGrammarInput($tokens);

        return $this->matcher($rule)->matchesRule($grammar, $rule, $input);
    }

    private function tokenizeForRule(Lexer $lexer, string $rule, string $source): TokenStream
    {
        if ($rule === 'source-file' || str_starts_with($source, '<?')) {
            return $lexer->tokenize($source)->withoutTrivia();
        }

        $tokens = $lexer->tokenize('<?php ' . $source)->withoutTrivia()->all();
        array_shift($tokens);

        return new TokenStream(array_values($tokens));
    }

    private function lexerFor(string $version): Lexer
    {
        $package = $this->grammars->manifest()->package($version);

        return Lexer::forVersion($package->lexerVersion);
    }

    private function matcher(string $rootRule): Matcher
    {
        return new Matcher(
            rootRule: $rootRule,
            primitiveMatchers: $this->lexicalPrimitiveMatchers(),
            primitiveMatchersOverrideProductions: true,
            coverage: $this->coverage,
        );
    }

    /**
     * @return array<string, callable(Input, int): list<int>>
     */
    private function lexicalPrimitiveMatchers(): array
    {
        return [
            'code-unit' => static fn (Input $input, int $offset): array => $offset < $input->length() ? [$offset + 1] : [],
            'inline-html-text' => self::tokenTypeMatcher(TokenType::InlineHtml),
            'identifier' => self::tokenTypeMatcher(TokenType::Identifier),
            'semi-reserved-identifier' => self::semiReservedIdentifierMatcher(),
            'name-identifier' => self::semiReservedIdentifierMatcher(),
            'variable' => self::tokenTypeMatcher(TokenType::Variable),
            'integer-literal' => self::tokenTypeMatcher(TokenType::IntegerLiteral),
            'decimal-integer-literal' => self::lexemeMatcher(TokenType::IntegerLiteral, '/^(?:0|[1-9](?:_?[0-9])*)$/'),
            'binary-integer-literal' => self::lexemeMatcher(TokenType::IntegerLiteral, '/^0[bB][01](?:_?[01])*$/'),
            'octal-integer-literal' => self::lexemeMatcher(TokenType::IntegerLiteral, '/^0[0-7](?:_?[0-7])*$/'),
            'explicit-octal-integer-literal' => self::lexemeMatcher(TokenType::IntegerLiteral, '/^0[oO][0-7](?:_?[0-7])*$/'),
            'hexadecimal-integer-literal' => self::lexemeMatcher(TokenType::IntegerLiteral, '/^0[xX][0-9A-Fa-f](?:_?[0-9A-Fa-f])*$/'),
            'floating-literal' => self::lexemeMatcher(TokenType::FloatingLiteral, '/^(?:(?:[0-9](?:_?[0-9])*)?\.[0-9](?:_?[0-9])*|[0-9](?:_?[0-9])*\.(?:[0-9](?:_?[0-9])*)?)(?:[eE][+-]?[0-9](?:_?[0-9])*)?$|^[0-9](?:_?[0-9])*[eE][+-]?[0-9](?:_?[0-9])*$/'),
            'string-literal' => self::tokenTypesMatcher(TokenType::StringLiteral, TokenType::HeredocString, TokenType::NowdocString),
            'single-quoted-string' => self::tokenTypeMatcher(TokenType::StringLiteral),
            'double-quoted-string' => self::tokenTypeMatcher(TokenType::StringLiteral),
            'heredoc-string' => self::tokenTypeMatcher(TokenType::HeredocString),
            'nowdoc-string' => self::tokenTypeMatcher(TokenType::NowdocString),
        ];
    }

    private static function tokenTypeMatcher(TokenType $type): callable
    {
        return self::tokenTypesMatcher($type);
    }

    private static function tokenTypesMatcher(TokenType ...$types): callable
    {
        return static function (Input $input, int $offset) use ($types): array {
            if (!$input instanceof PhpGrammarInput || $offset >= $input->length()) {
                return [];
            }

            return in_array($input->tokenAt($offset)->type, $types, true) ? [$offset + 1] : [];
        };
    }

    private static function lexemeMatcher(TokenType $type, string $pattern): callable
    {
        return static function (Input $input, int $offset) use ($type, $pattern): array {
            if (!$input instanceof PhpGrammarInput || $offset >= $input->length()) {
                return [];
            }

            $token = $input->tokenAt($offset);
            return $token->type === $type && preg_match($pattern, $token->lexeme) === 1 ? [$offset + 1] : [];
        };
    }

    private static function semiReservedIdentifierMatcher(): callable
    {
        return static function (Input $input, int $offset): array {
            if (!$input instanceof PhpGrammarInput || $offset >= $input->length()) {
                return [];
            }

            $token = $input->tokenAt($offset);
            if ($token->type === TokenType::Identifier) {
                return [$offset + 1];
            }

            $allowedContextualKeywords = ['enum' => true, 'readonly' => true];
            return $token->type === TokenType::Keyword && isset($allowedContextualKeywords[strtolower($token->lexeme)])
                ? [$offset + 1]
                : [];
        };
    }
}
