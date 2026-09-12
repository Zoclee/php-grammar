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
        '<=', '>=', '==', '!=', '<>', '??', '<<', '>>', '**',
        '...',
        '=', '|', '^', '&', '+', '-', '*', '/', '.', '%', '!', '~', '<', '>', '?', '@',
    ];

    private const PUNCTUATION = [';', ':', ',', '(', ')', '[', ']', '{', '}', '\\', '$'];

    // Zend's lookahead macros deliberately exclude NUL, unlike ordinary comments.
    private const LOOKAHEAD_TRIVIA = '(?:[ \t\r\n]+|/\*[^*\x00]*\*+(?:[^*/\x00][^*\x00]*\*+)*/|//[^\x00\r\n]*[\r\n]|\#(?:[^\[\x00][^\x00\r\n]*[\r\n]|[\r\n]))';

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

    public function withShortOpenTag(bool $enabled): self
    {
        return new self($this->version->withShortOpenTag($enabled));
    }

    public function tokenize(string $source): TokenStream
    {
        $state = new LexerState($source);
        $tokens = [];
        $inPhp = false;
        $lookingForProperty = false;
        $last = [];
        if (str_starts_with($source, '#!') && ($end = $this->findLineEnd($source, 0)) !== null) {
            $tokens[] = $state->consume($this->lineBreakEnd($source, $end), TokenType::Comment);
        }

        while (!$state->atEnd()) {
            if (count($last) === 4 && $last[0]->type === TokenType::Keyword && strtolower($last[0]->lexeme) === '__halt_compiler'
                && $last[1]->lexeme === '(' && $last[2]->lexeme === ')'
                && ($last[3]->lexeme === ';' || $last[3]->type === TokenType::CloseTag)) {
                $tokens[] = $state->consume(strlen($source) - $state->offset, TokenType::HaltCompilerData);
                break;
            }
            if (!$inPhp) {
                $pattern = $this->version->shortOpenTag ? '/<\?/i' : '/<\?(?:=|php(?=[ \t\r\n]|$))/i';
                $found = preg_match($pattern, $source, $opening, PREG_OFFSET_CAPTURE, $state->offset);
                $openOffset = $found === 1 ? $opening[0][1] : false;
                if ($openOffset === false) {
                    $tokens[] = $state->consume(strlen($source) - $state->offset, TokenType::InlineHtml);
                    break;
                }

                if ($openOffset > $state->offset) {
                    $tokens[] = $state->consume($openOffset - $state->offset, TokenType::InlineHtml);
                    $last = [];
                }

                $tokens[] = $this->consumeOpenTag($state);
                $inPhp = true;
                continue;
            }

            if ($state->startsWith('?>')) {
                $length = 2;
                if ($state->charAt(2) === "\r") {
                    $length += $state->charAt(3) === "\n" ? 2 : 1;
                } elseif ($state->charAt(2) === "\n") {
                    $length++;
                }
                $token = $state->consume($length, TokenType::CloseTag);
                $tokens[] = $token;
                $last = array_slice([...$last, $token], -4);
                $lookingForProperty = false;
                $inPhp = false;
                continue;
            }

            $token = $this->consumePhpToken($state, $lookingForProperty);
            $tokens[] = $token;
            if (!$token->isTrivia()) {
                $last = array_slice([...$last, $token], -4);
                $lookingForProperty = in_array($token->lexeme, ['->', '?->'], true);
            }
            if ($token->type === TokenType::Keyword && strtolower($token->lexeme) === 'yield') {
                $tail = $this->consumeYieldFromTail($state);
                array_push($tokens, ...$tail);
                if ($tail !== []) $last = array_slice([...$last, $tail[array_key_last($tail)]], -4);
            }
        }

        return new TokenStream($tokens);
    }

    /** @return list<Token> Zend's composite token, split for the EBNF terminals. */
    private function consumeYieldFromTail(LexerState $state): array
    {
        if (preg_match('~^(' . self::LOOKAHEAD_TRIVIA . '+)from(?![a-zA-Z0-9_\x80-\xff])~i',
            substr($state->source, $state->offset), $yieldFrom) !== 1) return [];
        /* Atomic even with ?> inside a line comment, including nested scripting. */
        $tokens = [];
        $end = $state->offset + strlen($yieldFrom[1]);
        while ($state->offset < $end) {
            preg_match('~^' . self::LOOKAHEAD_TRIVIA . '~', substr($state->source, $state->offset), $trivia);
            $text = $trivia[0];
            $type = $this->isWhitespace($text[0]) ? TokenType::Whitespace
                : (preg_match('~^/\*\*[ \t\r\n]~', $text) ? TokenType::DocComment : TokenType::Comment);
            $tokens[] = $state->consume(strlen($text), $type);
        }
        $tokens[] = $state->consume(4, TokenType::Keyword);
        return $tokens;
    }

    private function consumeOpenTag(LexerState $state): Token
    {
        if ($state->startsWith('<?=')) {
            return $state->consume(3, TokenType::EchoOpenTag);
        }

        if (strtolower(substr($state->source, $state->offset, 5)) === '<?php'
            && ($state->charAt(5) === null || in_array($state->charAt(5), [" ", "\t", "\r", "\n"], true))) {
            return $state->consume(5, TokenType::OpenTag);
        }

        if ($this->version->shortOpenTag && $state->startsWith('<?')) {
            return $state->consume(2, TokenType::OpenTag);
        }

        throw $state->error('Expected PHP open tag.');
    }

    private function consumePhpToken(LexerState $state, bool $lookingForProperty = false): Token
    {
        $char = $state->charAt(0);

        if ($lookingForProperty && $this->isIdentifierStart($char)) {
            return $this->consumeIdentifierOrKeyword($state, true);
        }

        if ($this->isWhitespace($char)) {
            return $this->consumeWhile($state, TokenType::Whitespace, fn (string $value): bool => $this->isWhitespace($value));
        }

        if ($state->startsWith('/**') && in_array($state->charAt(3), [" ", "\t", "\r", "\n"], true)) {
            return $this->consumeBlockComment($state, TokenType::DocComment);
        }

        if (!$lookingForProperty && $state->startsWith('#[')) {
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

        if (($char === 'b' || $char === 'B') && in_array($state->charAt(1), ["'", '"'], true)) {
            return $this->consumeQuotedString($state, $state->charAt(1), 1);
        }

        if ($char === '\'' || $char === '"' || $char === '`') {
            return $this->consumeQuotedString($state, $char);
        }

        if ($state->startsWith('<<<')) {
            return $this->consumeHereString($state);
        }
        if (($char === 'b' || $char === 'B') && substr($state->source, $state->offset + 1, 3) === '<<<') {
            return $this->consumeHereString($state, 1);
        }

        if (preg_match('/^\([ \t]*real[ \t]*\)/i', substr($state->source, $state->offset)) === 1) {
            throw $state->error('The (real) cast has been removed; use (float).');
        }

        if (preg_match('/^\([ \t]*(?:integer|double|boolean|binary|int|float|string|array|object|bool|void|unset)[ \t]*\)/i', substr($state->source, $state->offset), $cast) === 1) {
            return $state->consume(strlen($cast[0]), TokenType::Operator);
        }

        foreach (['public(set)', 'protected(set)', 'private(set)'] as $modifier) {
            if (strtolower(substr($state->source, $state->offset, strlen($modifier))) === $modifier) {
                return $state->consume(strlen($modifier), TokenType::Operator);
            }
        }

        if ($this->isNumberStart($state)) {
            return $this->consumeNumber($state);
        }

        $label = '[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*';
        $rest = substr($state->source, $state->offset);
        foreach ([
            TokenType::RelativeName->value => '/^namespace\\\\' . $label . '(?:\\\\' . $label . ')*/i',
            TokenType::FullyQualifiedName->value => '/^\\\\' . $label . '(?:\\\\' . $label . ')*/',
            TokenType::QualifiedName->value => '/^' . $label . '(?:\\\\' . $label . ')+/',
        ] as $type => $pattern) {
            if (preg_match($pattern, $rest, $name) === 1) {
                return $state->consume(strlen($name[0]), TokenType::from($type));
            }
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

    private function consumeQuotedString(LexerState $state, string $quote, int $prefix = 0): Token
    {
        $length = 1 + $prefix;
        while (!$state->atEnd($length)) {
            $char = $state->charAt($length);
            if ($char === '\\') {
                $length += $state->atEnd($length + 1) ? 1 : 2;
                continue;
            }

            if ($quote !== "'" && in_array(substr($state->source, $state->offset + $length, 2), ['{$', '${'], true)) {
                $opening = $state->offset + $length + ($char === '$' ? 1 : 0);
                $length = $this->interpolationEnd($state->source, $opening) - $state->offset + 1;
                continue;
            }

            $length++;
            if ($char === $quote) {
                return $state->consume($length, $quote === '`' ? TokenType::BacktickString : TokenType::StringLiteral);
            }
        }

        throw $state->error('Unterminated string literal.');
    }

    /** Match the scripting brace that restores the enclosing string scanner state. */
    public function interpolationEnd(string $source, int $opening): int
    {
        $state = new LexerState($source);
        $state->offset = $opening + 1;
        $depth = 1;
        $lookingForProperty = false;
        while (!$state->atEnd()) {
            if ($state->startsWith('?>')) {
                $pattern = $this->version->shortOpenTag ? '/<\?/i' : '/<\?(?:=|php(?=[ \t\r\n]|$))/i';
                if (!preg_match($pattern, $source, $tag, PREG_OFFSET_CAPTURE, $state->offset + 2)) break;
                $state->offset = $tag[0][1];
                $this->consumeOpenTag($state);
                $lookingForProperty = false;
                continue;
            }
            $token = $this->consumePhpToken($state, $lookingForProperty);
            if ($token->type === TokenType::Keyword && strtolower($token->lexeme) === 'yield') {
                $this->consumeYieldFromTail($state);
            }
            if ($token->lexeme === '{') $depth++;
            if ($token->lexeme === '}' && --$depth === 0) return $token->offset;
            if (!$token->isTrivia()) $lookingForProperty = in_array($token->lexeme, ['->', '?->'], true);
        }
        throw $state->error('Unterminated braced string interpolation.');
    }

    private function consumeHereString(LexerState $state, int $prefix = 0): Token
    {
        $lineEnd = $this->findLineEnd($state->source, $state->offset);
        if ($lineEnd === null) {
            throw $state->error('Unterminated heredoc or nowdoc header.');
        }

        $header = substr($state->source, $state->offset + 3 + $prefix, $lineEnd - ($state->offset + 3 + $prefix));
        $trimmed = ltrim($header, " \t");
        $type = TokenType::HeredocString;
        $labelPattern = '[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*';

        if (preg_match('/^(' . $labelPattern . ')$/D', $trimmed, $match) === 1) {
            $label = $match[1];
        } elseif (preg_match('/^\'(' . $labelPattern . ')\'$/D', $trimmed, $match) === 1) {
            $label = $match[1];
            $type = TokenType::NowdocString;
        } elseif (preg_match('/^"(' . $labelPattern . ')"$/D', $trimmed, $match) === 1) {
            $label = $match[1];
        } else {
            throw $state->error('Malformed heredoc or nowdoc label.');
        }

        $bodyStart = $this->lineBreakEnd($state->source, $lineEnd);
        $body = substr($state->source, $bodyStart);
        $pattern = '/(?:\A|(?<=[\r\n]))([ \t]*)' . preg_quote($label, '/') . '(?=[^A-Za-z0-9_\x80-\xff])/';
        $searchOffset = 0;
        $found = false;
        while (preg_match($pattern, $body, $match, PREG_OFFSET_CAPTURE, $searchOffset) === 1) {
            $candidate = $match[0][1];
            if ($type === TokenType::HeredocString) {
                for ($i = $searchOffset; $i < $candidate; $i++) {
                    if ($body[$i] === '\\') { $i++; continue; }
                    if (in_array(substr($body, $i, 2), ['{$', '${'], true)) {
                        $opening = $i + ($body[$i] === '$' ? 1 : 0);
                        $i = $this->interpolationEnd($body, $opening);
                        if ($i >= $candidate) {
                            $searchOffset = $i + 1;
                            continue 2;
                        }
                    }
                }
            }
            $found = true;
            break;
        }
        if (!$found) {
            throw $state->error('Unterminated heredoc or nowdoc body.');
        }

        $indent = $match[1][0];
        if (str_contains($indent, ' ') && str_contains($indent, "\t")) {
            throw $state->error('Heredoc closing indentation mixes spaces and tabs.');
        }
        if ($indent !== '') {
            $indentationBody = substr($body, 0, $match[0][1]);
            if ($type === TokenType::HeredocString) {
                for ($i = 0; $i < strlen($indentationBody); $i++) {
                    if ($body[$i] === '\\') { $i++; continue; }
                    if (in_array(substr($body, $i, 2), ['{$', '${'], true)) {
                        $end = $this->interpolationEnd($body, $i + ($body[$i] === '$' ? 1 : 0));
                        // Nested scripting lines are not heredoc text to be dedented.
                        $indentationBody = substr_replace($indentationBody, 'X' . str_repeat(' ', $end - $i), $i, $end - $i + 1);
                        $i = $end;
                    }
                }
            }
            foreach (preg_split('/\r\n|\r|\n/', $indentationBody) as $line) {
                if (trim($line, " \t") === '') {
                    $prefix = substr($line, 0, min(strlen($line), strlen($indent)));
                    if ($prefix !== str_repeat($indent[0], strlen($prefix))) {
                        throw $state->error('Heredoc body indentation mixes spaces and tabs.');
                    }
                } elseif (!str_starts_with($line, $indent)) {
                    throw $state->error('Heredoc body indentation is less than the closing label.');
                }
            }
        }
        $endOffset = $bodyStart + $match[0][1] + strlen($match[0][0]);

        return $state->consume($endOffset - $state->offset, $type);
    }

    private function consumeNumber(LexerState $state): Token
    {
        $rest = substr($state->source, $state->offset);
        $digits = '[0-9](?:_?[0-9])*';
        $fraction = '(?:' . $digits . '\.(?:' . $digits . ')?|\.' . $digits . ')';
        foreach ([
            '/^(?:' . $fraction . '|' . $digits . ')[eE][+-]?' . $digits . '/',
            '/^' . $fraction . '/',
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

    private function consumeIdentifierOrKeyword(LexerState $state, bool $forceIdentifier = false): Token
    {
        $length = 1;
        while ($this->isIdentifierPart($state->charAt($length))) {
            $length++;
        }

        $lexeme = substr($state->source, $state->offset, $length);
        if ($forceIdentifier) {
            return $state->consume($length, TokenType::Identifier);
        }
        if (strtolower($lexeme) === 'enum') {
            $tail = substr($state->source, $state->offset + $length);
            $trivia = self::LOOKAHEAD_TRIVIA . '+';
            // The longer exclusion rule has no identifier-end assertion.
            $enumKeyword = preg_match('~^' . $trivia . '[a-zA-Z_\x80-\xff]~', $tail) === 1
                && preg_match('~^' . $trivia . '(?:extends|implements)~i', $tail) !== 1;
            return $state->consume($length, $enumKeyword ? TokenType::Keyword : TokenType::Identifier);
        }
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
        return $char !== null && str_contains(" \n\r\t", $char);
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
