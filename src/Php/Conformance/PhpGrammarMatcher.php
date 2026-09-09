<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

use PhpGrammar\Ebnf\Matching\Input;
use PhpGrammar\Ebnf\Matching\Matcher;
use PhpGrammar\Ebnf\Matching\MatchResult;
use PhpGrammar\Ebnf\Matching\StringInput;
use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Php\Lexing\LexerException;
use PhpGrammar\Php\Lexing\TokenStream;
use PhpGrammar\Php\Lexing\TokenType;

final readonly class PhpGrammarMatcher
{
    public function __construct(
        private GrammarRepository $grammars,
    ) {
    }

    public static function forRepositoryRoot(string $repositoryRoot): self
    {
        return new self(new GrammarRepository($repositoryRoot));
    }

    public function matches(string $version, string $source): MatchResult
    {
        return $this->matchesRule($version, 'source-file', $source);
    }

    public function matchesRule(string $version, string $rule, string $source): MatchResult
    {
        $grammar = $this->grammars->load($version);
        try {
            $tokens = $this->tokenizeForRule($version, $rule, $source);
        } catch (LexerException $exception) {
            return new MatchResult(false, $rule, new StringInput($source), 0, [$exception->getMessage()]);
        }

        $input = new PhpGrammarInput($tokens);

        return $this->matcher($rule)->matchesRule($grammar, $rule, $input);
    }

    private function tokenizeForRule(string $version, string $rule, string $source): TokenStream
    {
        $lexer = $this->lexerFor($version);
        if ($rule === 'source-file' || str_starts_with($source, '<?')) {
            return $lexer->tokenize($source)->withoutTrivia();
        }

        $tokens = $lexer->tokenize('<?php ' . $source)->withoutTrivia()->all();
        array_shift($tokens);

        return new TokenStream(array_values($tokens));
    }

    private function lexerFor(string $version): Lexer
    {
        return match ($version) {
            '8.5' => Lexer::forPhp85(),
            default => throw new ConformanceException(sprintf('No PHP lexer configured for grammar version "%s".', $version)),
        };
    }

    private function matcher(string $rootRule): Matcher
    {
        return new Matcher(
            rootRule: $rootRule,
            primitiveMatchers: $this->lexicalPrimitiveMatchers(),
            primitiveMatchersOverrideProductions: true,
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
            'variable' => self::tokenTypeMatcher(TokenType::Variable),
            'integer-literal' => self::tokenTypeMatcher(TokenType::IntegerLiteral),
            'decimal-integer-literal' => self::tokenTypeMatcher(TokenType::IntegerLiteral),
            'binary-integer-literal' => self::tokenTypeMatcher(TokenType::IntegerLiteral),
            'octal-integer-literal' => self::tokenTypeMatcher(TokenType::IntegerLiteral),
            'explicit-octal-integer-literal' => self::tokenTypeMatcher(TokenType::IntegerLiteral),
            'hexadecimal-integer-literal' => self::tokenTypeMatcher(TokenType::IntegerLiteral),
            'floating-literal' => self::tokenTypeMatcher(TokenType::FloatingLiteral),
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
}
