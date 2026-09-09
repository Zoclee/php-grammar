<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Lexing;

final class Lexer
{
    /** @var non-empty-list<string> */
    private const OPERATORS = [
        'public(set)', 'protected(set)', 'private(set)',
        '===', '!==', '<=>', '??=', '<<=', '>>=', '**=',
        '+=', '-=', '*=', '/=', '.=', '%=', '&=', '|=', '^=',
        '|>', '?->', '->', '::', '=>', '++', '--', '&&', '||',
        '<=', '>=', '==', '!=', '??', '<<', '>>', '**',
        '...',
        '=', '|', '^', '&', '+', '-', '*', '/', '.', '%', '!', '~', '<', '>', '?', '@',
    ];

    private const PUNCTUATION = [';', ':', ',', '(', ')', '[', ']', '{', '}', '\\', '$'];

    public function __construct(
        private readonly PhpVersion $version,
    ) {
    }

    public static function forPhp85(): self
    {
        return new self(PhpVersion::php85());
    }

    public static function forVersion(string $version): self
    {
        return new self(PhpVersion::forVersion($version));
    }

    public function tokenize(string $source): TokenStream
    {
        $state = new LexerState($source);
        $tokens = [];
        $inPhp = false;

        while (!$state->atEnd()) {
            if (!$inPhp) {
                $openOffset = strpos($source, '<?', $state->offset);
                if ($openOffset === false) {
                    $tokens[] = $state->consume(strlen($source) - $state->offset, TokenType::InlineHtml);
                    break;
                }

                if ($openOffset > $state->offset) {
                    $tokens[] = $state->consume($openOffset - $state->offset, TokenType::InlineHtml);
                }

                $tokens[] = $this->consumeOpenTag($state);
                $inPhp = true;
                continue;
            }

            if ($state->startsWith('?>')) {
                $tokens[] = $state->consume(2, TokenType::CloseTag);
                $inPhp = false;
                continue;
            }

            $tokens[] = $this->consumePhpToken($state);
        }

        return new TokenStream($tokens);
    }

    private function consumeOpenTag(LexerState $state): Token
    {
        if ($state->startsWith('<?=')) {
            return $state->consume(3, TokenType::EchoOpenTag);
        }

        if ($state->startsWith('<?php') && !$this->isIdentifierPart($state->charAt(5))) {
            return $state->consume(5, TokenType::OpenTag);
        }

        if ($this->version->shortOpenTag && $state->startsWith('<?')) {
            return $state->consume(2, TokenType::OpenTag);
        }

        throw $state->error('Expected PHP open tag.');
    }

    private function consumePhpToken(LexerState $state): Token
    {
        $char = $state->charAt(0);

        if ($this->isWhitespace($char)) {
            return $this->consumeWhile($state, TokenType::Whitespace, fn (string $value): bool => $this->isWhitespace($value));
        }

        if ($state->startsWith('/**')) {
            return $this->consumeBlockComment($state, TokenType::DocComment);
        }

        if ($state->startsWith('#[')) {
            return $state->consume(2, TokenType::Punctuation);
        }

        if ($state->startsWith('/*')) {
            return $this->consumeBlockComment($state, TokenType::Comment);
        }

        if ($state->startsWith('//') || $state->startsWith('#')) {
            return $this->consumeLineComment($state);
        }

        if ($char === '$' && $this->isIdentifierStart($state->charAt(1))) {
            return $this->consumeVariable($state);
        }

        if ($char === '\'' || $char === '"') {
            return $this->consumeQuotedString($state, $char);
        }

        if ($state->startsWith('<<<')) {
            return $this->consumeHereString($state);
        }

        foreach ([
            '(integer)', '(double)', '(boolean)',
            '(binary)', '(int)', '(float)', '(string)', '(array)', '(object)', '(bool)', '(void)',
        ] as $cast) {
            if ($state->startsWith($cast)) {
                return $state->consume(strlen($cast), TokenType::Operator);
            }
        }

        if ($this->isNumberStart($state)) {
            return $this->consumeNumber($state);
        }

        if ($this->isIdentifierStart($char)) {
            return $this->consumeIdentifierOrKeyword($state);
        }

        foreach (self::OPERATORS as $operator) {
            if ($state->startsWith($operator)) {
                return $state->consume(strlen($operator), TokenType::Operator);
            }
        }

        if (in_array($char, self::PUNCTUATION, true)) {
            return $state->consume(1, TokenType::Punctuation);
        }

        throw $state->error(sprintf('Unexpected character %s.', var_export($char, true)));
    }

    private function consumeBlockComment(LexerState $state, TokenType $type): Token
    {
        $end = strpos($state->source, '*/', $state->offset + 2);
        if ($end === false) {
            throw $state->error('Unterminated block comment.');
        }

        return $state->consume($end + 2 - $state->offset, $type);
    }

    private function consumeLineComment(LexerState $state): Token
    {
        $length = 0;
        while (!$state->atEnd($length) && !in_array($state->charAt($length), ["\r", "\n"], true)) {
            if ($state->charAt($length) === '?' && $state->charAt($length + 1) === '>') {
                break;
            }

            $length++;
        }

        return $state->consume($length, TokenType::Comment);
    }

    private function consumeVariable(LexerState $state): Token
    {
        $length = 2;
        while ($this->isIdentifierPart($state->charAt($length))) {
            $length++;
        }

        return $state->consume($length, TokenType::Variable);
    }

    private function consumeQuotedString(LexerState $state, string $quote): Token
    {
        $length = 1;
        while (!$state->atEnd($length)) {
            $char = $state->charAt($length);
            if ($char === '\\') {
                $length += $state->atEnd($length + 1) ? 1 : 2;
                continue;
            }

            $length++;
            if ($char === $quote) {
                return $state->consume($length, TokenType::StringLiteral);
            }
        }

        throw $state->error('Unterminated string literal.');
    }

    private function consumeHereString(LexerState $state): Token
    {
        $lineEnd = $this->findLineEnd($state->source, $state->offset);
        if ($lineEnd === null) {
            throw $state->error('Unterminated heredoc or nowdoc header.');
        }

        $header = substr($state->source, $state->offset + 3, $lineEnd - ($state->offset + 3));
        $trimmed = trim($header);
        $type = TokenType::HeredocString;

        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)$/', $trimmed, $match) === 1) {
            $label = $match[1];
        } elseif (preg_match('/^\'([A-Za-z_][A-Za-z0-9_]*)\'$/', $trimmed, $match) === 1) {
            $label = $match[1];
            $type = TokenType::NowdocString;
        } else {
            throw $state->error('Malformed heredoc or nowdoc label.');
        }

        $bodyStart = $this->lineBreakEnd($state->source, $lineEnd);
        $pattern = '/(?:^|\R)' . preg_quote($label, '/') . ';?(?=\R|$)/';
        if (preg_match($pattern, $state->source, $match, PREG_OFFSET_CAPTURE, $bodyStart) !== 1) {
            throw $state->error('Unterminated heredoc or nowdoc body.');
        }

        $endOffset = $match[0][1] + strlen($match[0][0]);
        if (str_ends_with($match[0][0], ';')) {
            $endOffset--;
        }

        return $state->consume($endOffset - $state->offset, $type);
    }

    private function consumeNumber(LexerState $state): Token
    {
        $rest = substr($state->source, $state->offset);

        foreach ([
            '/^(?:[0-9]+(?:_[0-9]+)*(?:\.[0-9]*(?:_[0-9]+)*)?|\.[0-9]+(?:_[0-9]+)*)(?:[eE][+-]?[0-9]+(?:_[0-9]+)*)/',
            '/^(?:[0-9]+(?:_[0-9]+)*\.[0-9]*(?:_[0-9]+)*|\.[0-9]+(?:_[0-9]+)*)/',
        ] as $pattern) {
            if (preg_match($pattern, $rest, $match) === 1) {
                return $state->consume(strlen($match[0]), TokenType::FloatingLiteral);
            }
        }

        foreach ([
            '/^0[xX][0-9A-Fa-f]+(?:_[0-9A-Fa-f]+)*/',
            '/^0[bB][01]+(?:_[01]+)*/',
            '/^0[oO][0-7]+(?:_[0-7]+)*/',
            '/^[0-9]+(?:_[0-9]+)*/',
        ] as $pattern) {
            if (preg_match($pattern, $rest, $match) === 1) {
                return $state->consume(strlen($match[0]), TokenType::IntegerLiteral);
            }
        }

        throw $state->error('Malformed numeric literal.');
    }

    private function consumeIdentifierOrKeyword(LexerState $state): Token
    {
        $length = 1;
        while ($this->isIdentifierPart($state->charAt($length))) {
            $length++;
        }

        $lexeme = substr($state->source, $state->offset, $length);
        return $state->consume($length, $this->version->isKeyword($lexeme) ? TokenType::Keyword : TokenType::Identifier);
    }

    private function consumeWhile(LexerState $state, TokenType $type, callable $predicate): Token
    {
        $length = 0;
        while (!$state->atEnd($length) && $predicate($state->charAt($length))) {
            $length++;
        }

        return $state->consume($length, $type);
    }

    private function isWhitespace(?string $char): bool
    {
        return $char !== null && str_contains(" \n\r\t\f\v", $char);
    }

    private function isNumberStart(LexerState $state): bool
    {
        return ctype_digit($state->charAt(0) ?? '')
            || ($state->charAt(0) === '.' && ctype_digit($state->charAt(1) ?? ''));
    }

    private function isIdentifierStart(?string $char): bool
    {
        return $char !== null && (preg_match('/[A-Za-z_\x80-\xff]/', $char) === 1);
    }

    private function isIdentifierPart(?string $char): bool
    {
        return $char !== null && (preg_match('/[A-Za-z0-9_\x80-\xff]/', $char) === 1);
    }

    private function findLineEnd(string $source, int $offset): ?int
    {
        $length = strlen($source);
        for ($index = $offset; $index < $length; $index++) {
            if ($source[$index] === "\r" || $source[$index] === "\n") {
                return $index;
            }
        }

        return null;
    }

    private function lineBreakEnd(string $source, int $lineEnd): int
    {
        if (($source[$lineEnd] ?? null) === "\r" && ($source[$lineEnd + 1] ?? null) === "\n") {
            return $lineEnd + 2;
        }

        return $lineEnd + 1;
    }
}
