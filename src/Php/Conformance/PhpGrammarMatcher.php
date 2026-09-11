<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

use PhpGrammar\Ebnf\Coverage\CoverageCollector;
use PhpGrammar\Ebnf\Matching\Input;
use PhpGrammar\Ebnf\Matching\ChartMatcher;
use PhpGrammar\Ebnf\Matching\MatchResult;
use PhpGrammar\Ebnf\Matching\StringInput;
use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Php\Lexing\LexerException;
use PhpGrammar\Php\Lexing\TokenStream;
use PhpGrammar\Php\Lexing\Token;
use PhpGrammar\Php\Lexing\StringSyntax;
use PhpGrammar\Php\Lexing\TokenType;
use PhpGrammar\Repository\RepositoryManifest;

final readonly class PhpGrammarMatcher
{
    public function __construct(
        private GrammarRepository $grammars,
        private ?CoverageCollector $coverage = null,
        private bool $shortOpenTag = true,
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
        return new self($this->grammars, $coverage, $this->shortOpenTag);
    }

    public function withShortOpenTag(bool $enabled): self
    {
        return new self($this->grammars, $this->coverage, $enabled);
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
            foreach ($tokens->all() as $token) {
                if ($token->type === TokenType::HeredocString || $token->type === TokenType::BacktickString
                    || ($token->type === TokenType::StringLiteral && preg_match('/^[bB]?"/', $token->lexeme))) {
                    foreach (StringSyntax::fragments($token->lexeme, $this->shortOpenTag) as [$fragmentRule, $fragment]) {
                        if (!$this->matchesRule($version, $fragmentRule, $fragment)->matched) {
                            throw new LexerException('Invalid ' . $fragmentRule . ' in string interpolation.');
                        }
                    }
                }
            }
        } catch (LexerException $exception) {
            return new MatchResult(false, $rule, new StringInput($source), 0, [$exception->getMessage()]);
        }

        $input = new PhpGrammarInput($tokens);

        return $this->matcher()->matchesRule($grammar, $rule, $input);
    }

    private function tokenizeForRule(Lexer $lexer, string $rule, string $source): TokenStream
    {
        $wholeSource = $rule === 'source-file' || str_starts_with($source, '<?');
        $tokens = $lexer->tokenize($wholeSource ? $source : '<?php ' . $source)->withoutTrivia()->all();
        $result = [];
        foreach ($tokens as $token) {
            if ($token->type === TokenType::OpenTag) {
                continue;
            }
            if ($token->type === TokenType::EchoOpenTag || $token->type === TokenType::CloseTag) {
                $token = new Token(
                    $token->type === TokenType::EchoOpenTag ? TokenType::Keyword : TokenType::Punctuation,
                    $token->type === TokenType::EchoOpenTag ? 'echo' : ';',
                    $token->offset, $token->length, $token->line, $token->column,
                );
            }
            $result[] = $token;
        }
        return new TokenStream($result);
    }

    private function lexerFor(string $version): Lexer
    {
        $package = $this->grammars->manifest()->package($version);

        return Lexer::forVersion($package->lexerVersion)->withShortOpenTag($this->shortOpenTag);
    }

    private function matcher(): ChartMatcher
    {
        return new ChartMatcher(
            primitives: $this->lexicalPrimitiveMatchers(),
            coverage: $this->coverage,
        );
    }

    /** @return list<string> Production bodies replaced by token primitives. */
    public function lexicalPrimitiveNames(): array
    {
        return array_keys($this->lexicalPrimitiveMatchers());
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
            'qualified-name' => self::tokenTypeMatcher(TokenType::QualifiedName),
            'fully-qualified-name' => self::tokenTypeMatcher(TokenType::FullyQualifiedName),
            'namespace-relative-name' => self::tokenTypeMatcher(TokenType::RelativeName),
            'semi-reserved-identifier' => self::semiReservedIdentifierMatcher(),
            'name-identifier' => self::lexemeMatcher(TokenType::Identifier, '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/'),
            'variable' => self::tokenTypeMatcher(TokenType::Variable),
            'decimal-integer-literal' => self::lexemeMatcher(TokenType::IntegerLiteral, '/^(?:0|[1-9](?:_?[0-9])*)$/'),
            'binary-integer-literal' => self::lexemeMatcher(TokenType::IntegerLiteral, '/^0[bB][01](?:_?[01])*$/'),
            'octal-integer-literal' => self::lexemeMatcher(TokenType::IntegerLiteral, '/^0(?:_?[0-7])+$/'),
            'explicit-octal-integer-literal' => self::lexemeMatcher(TokenType::IntegerLiteral, '/^0[oO][0-7](?:_?[0-7])*$/'),
            'hexadecimal-integer-literal' => self::lexemeMatcher(TokenType::IntegerLiteral, '/^0[xX][0-9A-Fa-f](?:_?[0-9A-Fa-f])*$/'),
            'floating-literal' => self::lexemeMatcher(TokenType::FloatingLiteral, '/^(?:(?:[0-9](?:_?[0-9])*)?\.[0-9](?:_?[0-9])*|[0-9](?:_?[0-9])*\.(?:[0-9](?:_?[0-9])*)?)(?:[eE][+-]?[0-9](?:_?[0-9])*)?$|^[0-9](?:_?[0-9])*[eE][+-]?[0-9](?:_?[0-9])*$/'),
            'string-literal' => self::tokenTypesMatcher(TokenType::StringLiteral, TokenType::HeredocString, TokenType::NowdocString),
            'constant-string-literal' => self::constantStringMatcher(),
            'constant-double-quoted-string' => self::constantStringMatcher(true),
            'single-quoted-string' => self::lexemeMatcher(TokenType::StringLiteral, '/^[bB]?\'/'),
            'double-quoted-string' => self::lexemeMatcher(TokenType::StringLiteral, '/^[bB]?"/'),
            'backtick-string' => self::tokenTypeMatcher(TokenType::BacktickString),
            'halt-compiler-data' => static function (Input $input, int $offset): array {
                return $offset === $input->length() ? [$offset] : (self::tokenTypeMatcher(TokenType::HaltCompilerData))($input, $offset);
            },
            'heredoc-string' => self::tokenTypeMatcher(TokenType::HeredocString),
            'nowdoc-string' => self::tokenTypeMatcher(TokenType::NowdocString),
        ];
    }

    private static function tokenTypeMatcher(TokenType $type): callable
    {
        return self::tokenTypesMatcher($type);
    }

    private static function constantStringMatcher(bool $doubleQuotedOnly = false): callable
    {
        return static function (Input $input, int $offset) use ($doubleQuotedOnly): array {
            if (!$input instanceof PhpGrammarInput || $offset >= $input->length()) return [];
            $token = $input->tokenAt($offset);
            if ($doubleQuotedOnly && ($token->type !== TokenType::StringLiteral || !preg_match('/^[bB]?"/', $token->lexeme))) return [];
            if ($token->type === TokenType::NowdocString) return [$offset + 1];
            if (!in_array($token->type, [TokenType::StringLiteral, TokenType::HeredocString], true)) return [];
            if (preg_match('/^[bB]?\'/', $token->lexeme)) return [$offset + 1];
            for ($i = 0; $i < strlen($token->lexeme); $i++) {
                if ($token->lexeme[$i] === '\\') { $i++; continue; }
                if ($token->lexeme[$i] === '$' && preg_match('/[a-zA-Z_\x80-\xff{]/', $token->lexeme[$i + 1] ?? '') === 1) return [];
            }
            return [$offset + 1];
        };
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

            return $token->type === TokenType::Keyword && strtolower($token->lexeme) !== '__halt_compiler'
                && $token->length === strlen($token->lexeme)
                ? [$offset + 1]
                : [];
        };
    }
}
