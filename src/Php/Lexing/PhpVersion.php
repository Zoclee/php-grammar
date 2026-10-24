<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Lexing;

final readonly class PhpVersion
{
    /**
     * @param array<string, true> $keywords
     */
    public function __construct(
        public string $version,
        private array $keywords,
        public bool $shortOpenTag = true,
    ) {
    }

    public static function php85(): self
    {
        return new self('8.5', self::keywords());
    }

    /** RC4 retains the keyword/token contract; deprecations do not reserve names. */
    public static function php86(): self
    {
        return new self('8.6', self::keywords());
    }

    /** @return array<string, true> Shared spellings, independent version identities. */
    private static function keywords(): array
    {
        $keywords = [
            '__halt_compiler', 'abstract', 'and', 'array', 'as', 'break',
            'callable', 'case', 'catch', 'class', 'clone', 'const',
            'continue', 'declare', 'default', 'do', 'echo', 'else',
            'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach',
            'endif', 'endswitch', 'endwhile', 'enum', 'eval', 'exit',
            'extends', 'final', 'finally', 'fn', 'for', 'foreach',
            'function', 'global', 'goto', 'if', 'implements', 'include',
            'include_once', 'instanceof', 'insteadof', 'interface',
            'isset', 'list', 'match', 'namespace', 'new', 'or',
            'print', 'private', 'protected', 'public', 'readonly',
            'require', 'require_once', 'return', 'static', 'switch',
            'throw', 'trait', 'try', 'unset', 'use', 'var',
            'while', 'xor', 'yield', 'die',
            '__line__', '__file__', '__dir__', '__class__', '__trait__', '__method__',
            '__function__', '__property__', '__namespace__',
        ];

        return array_fill_keys($keywords, true);
    }

    public static function forVersion(string $version): self
    {
        return match ($version) {
            '8.5' => self::php85(),
            '8.6' => self::php86(),
            default => throw new LexerException(sprintf('No PHP lexical configuration exists for version "%s".', $version)),
        };
    }

    public function isKeyword(string $identifier): bool
    {
        return isset($this->keywords[strtolower($identifier)]);
    }

    public function withShortOpenTag(bool $enabled): self
    {
        return new self($this->version, $this->keywords, $enabled);
    }
}
