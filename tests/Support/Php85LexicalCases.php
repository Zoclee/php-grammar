<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Support;

/** Independent source/expected-token matrices; never generated from the lexer. */
final class Php85LexicalCases
{
    public static function all(): array
    {
        $cases = [];
        $add = static function (string $id, string $family, array $tokens, bool $boundary = false, bool $short = true) use (&$cases): void {
            $cases[$id] = ['family' => $family, 'boundary' => $boundary, 'short' => $short,
                'source' => implode('', array_column($tokens, 1)), 'tokens' => $tokens];
        };
        $code = static function (string $id, string $family, array $tokens, bool $boundary = false) use ($add): void {
            $add($id, $family, [['open-tag', '<?php'], ['whitespace', ' '], ...$tokens], $boundary);
        };
        $error = static function (string $id, string $family, string $source, string $message) use (&$cases): void {
            $cases[$id] = ['family' => $family, 'boundary' => true, 'short' => true,
                'source' => '<?php ' . $source, 'error' => $message];
        };
        foreach ([false, true] as $short) {
            $suffix = $short ? '-short-on' : '-short-off';
            foreach (['', 'text < > <?xml', '<?phpX', '<?php_', '<?php0', "<?php\v", "<?php\f", "<?php\0"] as $i => $html) {
                if ($short && str_contains($html, '<?')) continue;
                $add('html-' . $i . $suffix, 'inline-html', $html === '' ? [] : [['inline-html', $html]], true, $short);
            }
            $add('tag-eof' . $suffix, 'tags', [['open-tag', '<?php']], true, $short);
            $add('echo-tag' . $suffix, 'tags', [['echo-open-tag', '<?='], ['integer-literal', '1'], ['close-tag', '?>']], false, $short);
            foreach ([" ", "\t", "\n", "\r", "\r\n", " \t\r\n\r\n"] as $i => $ws) {
                $add('tag-whitespace-' . $i . $suffix, 'tags', [['inline-html', 'head'], ['open-tag', '<?PhP'],
                    ['whitespace', $ws], ['keyword', 'echo'], ['whitespace', ' '], ['integer-literal', '1'],
                    ['close-tag', '?>' . ($i === 4 ? "\r\n" : '')], ['inline-html', 'tail'],
                    ['echo-open-tag', '<?='], ['integer-literal', '2'], ['close-tag', '?>']], false, $short);
            }
        }
        $add('short-degenerate', 'tags', [['open-tag', '<?'], ['identifier', 'phpX']], true);
        $add('short-plain', 'tags', [['open-tag', '<?'], ['whitespace', ' '], ['keyword', 'echo']], false);
        $add('ordinary-html', 'inline-html', [['inline-html', 'ordinary <html> text']], false, false);
        foreach (["\n", "\r", "\r\n"] as $i => $nl) {
            $add('shebang-' . $i, 'shebang', [['comment', '#!/usr/bin/php' . $nl], ['open-tag', '<?php'], ['whitespace', $nl], ['integer-literal', '1']]);
            $code('whitespace-' . $i, 'whitespace', [['variable', '$a'], ['whitespace', ' ' . "\t" . $nl], ['variable', '$b'], ['whitespace', $nl]], $i !== 0);
            foreach (['//', '#'] as $mark) {
                $code('comment-line-' . bin2hex($mark) . '-' . $i, 'comments', [['comment', $mark . ' /* " <?= '], ['whitespace', $nl], ['identifier', 'x']]);
                $code('comment-close-' . bin2hex($mark) . '-' . $i, 'comments', [['comment', $mark . ' x'], ['close-tag', '?>' . $nl], ['inline-html', 'tail']], true);
            }
        }
        $add('shebang-eof', 'shebang', [['inline-html', '#!/usr/bin/php']], true);
        $add('shebang-not-initial', 'shebang', [['inline-html', "x\n#!php\n"]], true);
        foreach (['/**/' => 'comment', '/***/' => 'comment', '/**x*/' => 'comment', '/** */' => 'doc-comment',
            "/**\t*/" => 'doc-comment', "/**\r\n*/" => 'doc-comment', "/**\v*/" => 'comment', '/* ?> <?php /* */' => 'comment',
            "/*\0*/" => 'comment', "//\0" => 'comment', '#' => 'comment', '//' => 'comment'] as $s => $type) {
            $code('comment-' . bin2hex($s), 'comments', [[$type, $s]], true);
        }
        $code('comment-first-end', 'comments', [['identifier', 'a'], ['comment', '/* */'], ['identifier', 'b'], ['operator', '*'], ['operator', '/']], true);
        $code('attribute-not-comment', 'comments', [['punctuation', '#['], ['identifier', 'A'], ['punctuation', ']']], true);
        foreach (['/*', '/** ', '/* x *'] as $s) $error('comment-unclosed-' . bin2hex($s), 'comments', $s, 'Unterminated block comment');

        // LABEL is a byte class, without Unicode or locale-dependent character tests.
        for ($byte = 0; $byte < 256; $byte++) {
            $c = chr($byte);
            $start = ($byte >= 65 && $byte <= 90) || ($byte >= 97 && $byte <= 122) || $byte === 95 || $byte >= 128;
            if ($start) {
                $code('identifier-start-' . $byte, 'identifiers', [['identifier', $c . '9']]);
                $code('identifier-part-' . $byte, 'identifiers', [['identifier', 'zz' . $c]], true);
                $code('variable-byte-' . $byte, 'variables', [['variable', '$' . $c . '1']]);
            } elseif ($byte >= 48 && $byte <= 57) {
                $code('identifier-digit-' . $byte, 'identifiers', [['integer-literal', $c], ['identifier', 'a']], true);
                $code('identifier-part-' . $byte, 'identifiers', [['identifier', 'a' . $c]], true);
                $code('variable-digit-' . $byte, 'variables', [['punctuation', '$'], ['integer-literal', $c]], true);
            } elseif (!str_contains(" \t\n\r", $c) && ($byte < 32 || $byte === 127)) {
                $error('bad-byte-' . $byte, 'bytes', $c, 'Unexpected character');
            }
            $add('html-byte-' . $byte, 'bytes', [['inline-html', 'x' . $c]], false, false);
            $code('single-byte-' . $byte, 'bytes', [['string-literal', "'" . ($c === "'" || $c === '\\' ? '\\' : '') . $c . "'"]]);
        }
        foreach (["\xc3\xa9", "\xf0\x9f\x92\xa9", "\xc0\xaf", "\xed\xa0\x80", "\xff\xfe"] as $i => $s) {
            $code('encoding-' . $i, 'bytes', [['identifier', $s], ['punctuation', ';'], ['variable', '$' . $s]], true);
        }
        $words = explode(' ', '__halt_compiler abstract and array as break callable case catch class clone const continue declare default do echo else elseif empty enddeclare endfor endforeach endif endswitch endwhile eval exit extends final finally fn for foreach function global goto if implements include include_once instanceof insteadof interface isset list match namespace new or print private protected public readonly require require_once return static switch throw trait try unset use var while xor yield die __LINE__ __FILE__ __DIR__ __CLASS__ __TRAIT__ __METHOD__ __FUNCTION__ __PROPERTY__ __NAMESPACE__');
        foreach ($words as $word) {
            foreach (array_unique([$word, strtoupper($word)]) as $spelling) $code('keyword-' . $spelling, 'keywords', [['keyword', $spelling]]);
            foreach (['x', '_', '0', "\xff"] as $suffix) $code('keyword-boundary-' . $word . '-' . bin2hex($suffix), 'keywords', [['identifier', $word . $suffix]], true);
            $code('property-keyword-' . $word, 'property', [['variable', '$o'], ['operator', '->'], ['identifier', $word]], true);
        }
        foreach (['from', 'enum', 'true', 'false', 'null', 'self', 'parent', 'int', 'float', 'string', 'bool', 'void', 'never', 'mixed', 'iterable', 'get', 'set'] as $word) $code('contextual-word-' . $word, 'keywords', [['identifier', $word]], true);
        foreach (['A', 'extends', 'extendsX', 'implements', 'implementsX', 'EXTENDS_', 'Xextends'] as $next) {
            $type = str_starts_with(strtolower($next), 'extends') || str_starts_with(strtolower($next), 'implements') ? 'identifier' : 'keyword';
            $code('enum-' . $next, 'enum', [[$type, 'enum'], ['whitespace', ' '], [in_array($next, ['extends', 'implements']) ? 'keyword' : 'identifier', $next]], $next !== 'A');
        }
        foreach ([['/*x*/', 'comment', true], ["/*\0*/", 'comment', false], ["//x\n", 'comment', true], ["#\n", 'comment', true]] as $i => [$trivia, $type, $lookahead]) {
            $code('yield-lookahead-' . $i, 'yield-from', [['keyword', 'yield'], [$type, $trivia], [$lookahead ? 'keyword' : 'identifier', 'from'], ['whitespace', ' '], ['integer-literal', '1']], true);
            // Ordinary line comments do not include the newline outside composite tokens.
            $parts = str_ends_with($trivia, "\n") ? [[$type, substr($trivia, 0, -1)], ['whitespace', "\n"]] : [[$type, $trivia]];
            $code('enum-lookahead-' . $i, 'enum', [[$lookahead ? 'keyword' : 'identifier', 'enum'], ...$parts, ['identifier', 'A']], true);
        }
        $code('yield-close-in-comment', 'yield-from', [['keyword', 'yield'], ['whitespace', ' '], ['comment', "// ?>\n"], ['keyword', 'from'], ['whitespace', ' '], ['integer-literal', '1']], true);
        $code('yield-eof', 'yield-from', [['keyword', 'yield'], ['whitespace', ' '], ['keyword', 'from']]);
        $code('yield-suffix', 'yield-from', [['keyword', 'yield'], ['whitespace', ' '], ['identifier', 'fromX']], true);
        $code('yield-comment-eof', 'yield-from', [['keyword', 'yield'], ['comment', '//from']], true);
        $code('yield-nested-scripting', 'yield-from', [['string-literal', "\"{\$o->{yield // ?>\nfrom [1]}}\""], ['punctuation', ';']], true);
        $code('variables', 'variables', [['variable', '$foo'], ['whitespace', ' '], ['variable', '$_foo'], ['whitespace', ' '], ['variable', '$foo1'], ['whitespace', ' '], ['punctuation', '$'], ['variable', '$foo'], ['punctuation', ';'], ['punctuation', '$'], ['punctuation', '{'], ['variable', '$x'], ['punctuation', '}']], true);
        foreach (['->', '?->'] as $op) {
            $code('property-comment-' . $op, 'property', [['variable', '$o'], ['operator', $op], ['comment', '/*x*/'], ['whitespace', ' '], ['identifier', 'match'], ['punctuation', ';'], ['keyword', 'match']]);
            $code('property-hash-' . $op, 'property', [['variable', '$o'], ['operator', $op], ['comment', '#[A]'], ['whitespace', "\n"], ['identifier', 'class']], true);
            $code('property-fallback-' . $op, 'property', [['variable', '$o'], ['operator', $op], ['punctuation', '{'], ['variable', '$p'], ['punctuation', '}'], ['punctuation', ';'], ['keyword', 'class']], true);
        }
        foreach (['a' => 'identifier', 'A\\B' => 'qualified-name', 'A\\match' => 'qualified-name', '\\A\\B' => 'fully-qualified-name', '\\match' => 'fully-qualified-name', 'namespace\\A\\match' => 'relative-name', 'NAMESPACE\\A' => 'relative-name'] as $s => $type) $code('name-' . $s, 'names', [[$type, $s]]);
        $code('name-repeated', 'names', [['identifier', 'A'], ['punctuation', '\\'], ['fully-qualified-name', '\\B']], true);
        $code('name-trailing', 'names', [['qualified-name', 'A\\B'], ['punctuation', '\\']], true);
        $code('name-space', 'names', [['identifier', 'A'], ['whitespace', ' '], ['fully-qualified-name', '\\B']], true);

        foreach (['0', '00', '01', '0_7', '123', '1_234', str_repeat('9', 80), '0x' . str_repeat('f', 80)] as $s) $code('integer-' . $s, 'integers', [['integer-literal', $s]]);
        foreach (['b' => '01', 'B' => '01', 'o' => '01234567', 'O' => '01234567', 'x' => '0123456789abcdefABCDEF', 'X' => '0123456789abcdefABCDEF'] as $prefix => $digits) {
            foreach (str_split($digits) as $digit) {
                foreach ([$digit, $digit . '_' . $digit] as $s) $code('radix-' . $prefix . $s, 'integers', [['integer-literal', '0' . $prefix . $s]]);
            }
            foreach (['', '_1'] as $tail) $code('radix-missing-' . $prefix . $tail, 'integers', [['integer-literal', '0'], ['identifier', $prefix . $tail]], true);
            foreach (['_', '__1', 'g'] as $tail) $code('radix-tail-' . $prefix . $tail, 'integers', [['integer-literal', '0' . $prefix . '1'], ['identifier', $tail]], true);
        }
        foreach (['b2', 'B9', 'o8', 'O9', 'xg', 'Xz'] as $s) $code('radix-invalid-' . $s, 'integers', [['integer-literal', '0'], ['identifier', $s]], true);
        $code('binary-termination', 'integers', [['integer-literal', '0b1'], ['integer-literal', '2']], true);
        $code('octal-termination', 'integers', [['integer-literal', '0o7'], ['integer-literal', '8']], true);
        // Invalid legacy octal digits are rejected by numeric primitives, after tokenization.
        foreach (['08', '09', '0_8', '0789'] as $s) $code('legacy-invalid-' . $s, 'integers', [['integer-literal', $s]], true);
        foreach (['1_', '1__2', '1a'] as $s) $code('decimal-tail-' . $s, 'integers', [['integer-literal', '1'], ['identifier', substr($s, 1)]], true);
        foreach (['1.', '.1', '1.2', '1_2.3_4', '00.9', '09e1'] as $s) $code('float-' . $s, 'floats', [['floating-literal', $s]]);
        foreach (['1', '1.', '.1', '1_2.3_4'] as $base) foreach (['e', 'E'] as $e) foreach (['', '+', '-'] as $sign) {
            $s = $base . $e . $sign . '1_2';
            $code('exponent-' . $s, 'floats', [['floating-literal', $s]]);
        }
        foreach (['1e' => [['integer-literal', '1'], ['identifier', 'e']], '1e+' => [['integer-literal', '1'], ['identifier', 'e'], ['operator', '+']],
            '1e_2' => [['integer-literal', '1'], ['identifier', 'e_2']], '1._2' => [['floating-literal', '1.'], ['identifier', '_2']],
            '1..2' => [['floating-literal', '1.'], ['floating-literal', '.2']], '1...2' => [['floating-literal', '1.'], ['operator', '.'], ['floating-literal', '.2']],
            '...2' => [['operator', '...'], ['integer-literal', '2']], '.1e2foo' => [['floating-literal', '.1e2'], ['identifier', 'foo']]] as $s => $tokens) $code('float-boundary-' . $s, 'floats', $tokens, true);
        $operators = explode(' ', '? ?? ??= ?-> -> => == === != !== <> < <= << <<= > >= >> >>= ** **= . .= ... | || |= |> & && &= ^ ^= + ++ += - -- -= / /= * *= % %= :: = ! ~ @ <=>');
        foreach ($operators as $op) {
            $code('operator-' . $op, 'operators', [['identifier', 'a'], ['operator', $op], ['identifier', 'b']]);
            $code('operator-eof-' . $op, 'operators', [['operator', $op]], true);
        }
        foreach (['====' => ['===', '='], '!===' => ['!==', '='], '????=' => ['??', '??='], '?->>' => ['?->', '>'], '****=' => ['**', '**='], '....' => ['...', '.'], '<<==' => ['<<=', '='], '|||>' => ['||', '|>'], '&&&=' => ['&&', '&='], '---=' => ['--', '-='], '+++=' => ['++', '+=']] as $s => $tokens) $code('operator-collision-' . $s, 'operators', array_map(static fn ($t) => ['operator', $t], $tokens), true);
        foreach (str_split(';:,()[]{}\\$') as $s) $code('punctuation-' . $s, 'operators', [['punctuation', $s]]);
        foreach (['public', 'protected', 'private'] as $v) {
            $code('set-visibility-' . $v, 'operators', [['operator', strtoupper($v) . '(SET)']]);
            $code('set-visibility-space-' . $v, 'operators', [['keyword', $v], ['whitespace', ' '], ['punctuation', '('], ['identifier', 'set'], ['punctuation', ')']], true);
        }
        foreach (['int', 'integer', 'float', 'double', 'bool', 'boolean', 'string', 'binary', 'array', 'object', 'void'] as $cast) {
            foreach (['', ' ', "\t", '  '] as $ws) $code('cast-' . $cast . '-' . bin2hex($ws), 'casts', [['operator', '(' . $ws . strtoupper($cast) . $ws . ')']]);
            $code('cast-newline-' . $cast, 'casts', [['punctuation', '('], ['whitespace', "\n"], [in_array($cast, ['array']) ? 'keyword' : 'identifier', $cast], ['punctuation', ')']], true);
        }
        $code('cast-removed-unset', 'casts', [['operator', '(unset)']], true);
        foreach (['(real)', '( REAL )', "(\treal\t)"] as $i => $s) $error('cast-removed-real-' . $i, 'casts', $s, 'The (real) cast has been removed');

        foreach (["'", '"', '`'] as $quote) {
            $family = match ($quote) { "'" => 'single-strings', '"' => 'double-strings', default => 'backticks' };
            $type = $quote === '`' ? 'backtick-string' : 'string-literal';
            foreach (['', 'text', "line\r\nline\rline\n", '\\' . $quote, '\\\\', '\\q', '?> <?php /* */', '$', '$9', '{x}', '\\$', '\\{', '"', "'", '`'] as $i => $body) {
                if ($body === $quote) continue;
                $code('string-' . $family . '-' . $i, $family, [[$type, $quote . $body . $quote], ['punctuation', ';'], ['keyword', 'echo']]);
            }
            $error('string-eof-' . $family, $family, $quote . 'text', 'Unterminated string literal');
            $error('string-escape-eof-' . $family, $family, $quote . 'text\\', 'Unterminated string literal');
            if ($quote !== '`') foreach (['b', 'B'] as $prefix) $code('string-prefix-' . $family . '-' . $prefix, $family, [[$type, $prefix . $quote . 'x' . $quote]]);
        }
        foreach (['$var', '{$var}', '${var}', '${match}', '${var[0]}', '$arr[key]', '$arr[0]', '$arr[-1]', '$arr[0xFF]', '$arr[$i]', '$obj->prop', '$obj?->prop', '$obj->9', '$obj-> prop', '{$obj->{"x"}}', '{$arr["}"]}', '{$obj->{/*}*/"x"}}', '{$obj->{"{$x}"}}', '$a[0][1]', '$o->p->q'] as $i => $body) {
            foreach (['"', '`'] as $quote) $code('interpolation-' . $i . '-' . $quote, 'interpolation', [[$quote === '`' ? 'backtick-string' : 'string-literal', $quote . $body . $quote], ['punctuation', ';'], ['keyword', 'echo']], $i > 1);
        }
        foreach (['n', 'r', 't', 'v', 'e', 'f', '$', '"', '`', '\\', '0', '07', '377', '777', 'x0', 'xFF', 'X41', 'x', 'u{0}', 'u{D800}', 'u{10FFFF}', 'u{00000041}', 'q'] as $escape) {
            foreach (['"', '`'] as $quote) $code('escape-' . bin2hex($quote . $escape), 'escapes', [[$quote === '`' ? 'backtick-string' : 'string-literal', $quote . '\\' . $escape . $quote]]);
        }
        foreach (['{}', '{xyz}', '{110000}', '{FFFFFFFFFFFF}', '{41'] as $escape) {
            // Boundary rejection lives in StringSyntax, after aggregation of string tokens.
            $code('escape-invalid-' . $escape, 'escapes', [['string-literal', '"\\u' . $escape . '"']], true);
        }
        foreach (['"{$x', '"${x', '`{$x'] as $i => $s) $error('interpolation-eof-' . $i, 'interpolation', $s, 'Unterminated braced string interpolation');
        foreach ([false, true] as $nowdoc) {
            $family = $nowdoc ? 'nowdoc' : 'heredoc';
            $type = $family . '-string';
            foreach (["\n", "\r", "\r\n"] as $n => $nl) foreach (['', '  ', "\t"] as $indent) {
                $header = '<<<' . ($nowdoc ? "'X'" : 'X') . $nl;
                $body = $indent . '$x \\q ${var}' . $nl;
                $code($family . '-indent-' . $n . '-' . bin2hex($indent), $family, [[$type, $header . $body . $indent . 'X'], ['punctuation', ';'], ['keyword', 'echo']]);
                $code($family . '-empty-' . $n . '-' . bin2hex($indent), $family, [[$type, $header . $indent . 'X'], ['punctuation', ';']], true);
                if ($indent !== '') {
                    $error($family . '-underindent-' . $n . '-' . bin2hex($indent), $family, $header . 'x' . $nl . $indent . 'X;', 'Heredoc body indentation');
                    $error($family . '-mixedindent-' . $n . '-' . bin2hex($indent), $family, $header . 'x' . $nl . " \tX;", 'Heredoc closing indentation');
                }
            }
            $header = $nowdoc ? "<<<'X'\n" : "<<<X\n";
            foreach (['Xx', 'X_', 'X0', "X\xff", 'x'] as $line) $code($family . '-label-prefix-' . bin2hex($line), $family, [[$type, $header . $line . "\nX"], ['punctuation', ';']], true);
            foreach ([';', ',', ')', '+'] as $after) $code($family . '-label-end-' . $after, $family, [[$type, $header . "text\nX"], [$after === '+' ? 'operator' : 'punctuation', $after]], true);
            foreach (['', 'X', "text\nX", "text\nY;", "text\nXx;"] as $i => $tail) $error($family . '-unclosed-' . $i, $family, $header . $tail, 'Unterminated heredoc or nowdoc body');
            foreach (['B', 'b'] as $prefix) $code($family . '-binary-' . $prefix, $family, [[$type, $prefix . $header . "body\nX"], ['punctuation', ';']]);
        }
        $code('heredoc-quoted-label', 'heredoc', [['heredoc-string', "<<< \t\"X\"\nbody\nX"], ['punctuation', ';']]);
        for ($byte = 128; $byte < 256; $byte++) {
            $label = chr($byte) . '0';
            $code('heredoc-label-byte-' . $byte, 'heredoc', [['heredoc-string', '<<<' . $label . "\n\0body\n" . $label], ['punctuation', ';']]);
            $code('nowdoc-label-byte-' . $byte, 'nowdoc', [['nowdoc-string', "<<<'" . $label . "'\n\0body\n" . $label], ['punctuation', ';']]);
        }
        foreach (["<<<X \nX;", "<<<1X\n1X;", "<<<'X\nX;", "<<<\vX\nX;"] as $i => $s) $error('heredoc-header-' . $i, 'heredoc', $s, 'Malformed heredoc or nowdoc label');
        $error('heredoc-header-eof', 'heredoc', '<<<X', 'Unterminated heredoc or nowdoc header');
        $code('heredoc-nested-label', 'heredoc', [['heredoc-string', "<<<OUT\n{\$o->{<<<INNER\nOUT\nINNER\n}}\nOUT"], ['punctuation', ';'], ['keyword', 'echo']], true);
        $code('nowdoc-literal-interpolation', 'nowdoc', [['nowdoc-string', "<<<'X'\n{\$unclosed \\u{no} ` \"\nX"], ['punctuation', ';']], true);
        foreach (['', "\0\xff<?php ?> \" <<<X\n", 'data'] as $i => $tail) $code('halt-' . $i, 'halt', [['keyword', '__halt_compiler'], ['punctuation', '('], ['comment', '/*x*/'], ['punctuation', ')'], ['punctuation', ';'], ...($tail === '' ? [] : [['halt-compiler-data', $tail]])]);
        $code('halt-close', 'halt', [['keyword', '__HALT_COMPILER'], ['punctuation', '('], ['punctuation', ')'], ['close-tag', "?>\r\n"], ['halt-compiler-data', '<?php broken']], true);
        $code('halt-malformed', 'halt', [['keyword', '__halt_compiler'], ['punctuation', '('], ['integer-literal', '1'], ['punctuation', ')'], ['punctuation', ';'], ['identifier', 'data']], true);
        $code('halt-property', 'halt', [['variable', '$o'], ['operator', '->'], ['identifier', '__halt_compiler'], ['punctuation', '('], ['punctuation', ')'], ['punctuation', ';'], ['identifier', 'data']], true);
        $code('halt-all-bytes', 'halt', [['keyword', '__halt_compiler'], ['punctuation', '('], ['punctuation', ')'], ['punctuation', ';'], ['halt-compiler-data', implode('', array_map(chr(...), range(0, 255)))]], true);
        return $cases;
    }

    /** Parser validity is independent of whole-string token recognition. */
    public static function primitives(): array
    {
        return [
            'identifiers' => ['identifier', ['abc', "\xff"], ['1a', 'class']],
            'name-identifiers' => ['name-identifier', ['_a'], ['class', 'a\\b']],
            'semi-reserved' => ['semi-reserved-identifier', ['match', 'readonly', '__PROPERTY__'], ['__halt_compiler', '1']],
            'variables' => ['variable', ['$a', "\$\xff"], ['$1', '$$a']],
            'qualified' => ['qualified-name', ['A\\match'], ['A\\', 'A\\\\B']],
            'fully-qualified' => ['fully-qualified-name', ['\\A\\B'], ['\\', '\\A\\']],
            'relative' => ['namespace-relative-name', ['namespace\\A'], ['namespace\\', 'A\\B']],
            'decimal' => ['decimal-integer-literal', ['0', '1_2'], ['08', '1__2']],
            'binary' => ['binary-integer-literal', ['0B1_0'], ['0b2', '0b_1']],
            'octal' => ['octal-integer-literal', ['00', '0_7'], ['08', '0_9']],
            'explicit-octal' => ['explicit-octal-integer-literal', ['0O7_0'], ['0o8', '0o_1']],
            'hex' => ['hexadecimal-integer-literal', ['0Xf_F'], ['0xg', '0x_1']],
            'float' => ['floating-literal', ['.1', '1.', '1E+2'], ['1e', '1e_2']],
            'single' => ['single-quoted-string', ["'\\u{bad}'", "'\\q'"], ["'x", '"x"']],
            'double' => ['double-quoted-string', ['"\\u{10ffff}"', '"\\xZ"', '"\\777"'], ['"\\u{}"', '"\\u{110000}"', '"\\u{xyz}"']],
            'constant-double' => ['constant-double-quoted-string', ['"x"', '"\\$x"'], ['"$x"', '"{$x}"']],
            'constant-string' => ['constant-string-literal', ["'x'", "<<<'X'\n\$x\nX"], ['"$x"', "<<<X\n\$x\nX"]],
            'string' => ['string-literal', ['"{$a[0]}"'], ['"{$a + 1}"', '"$a[1.0]"']],
            'backticks' => ['backtick-string', ['``', '`$a`', '`"`'], ['`x', '`\\u{110000}`']],
            'heredoc' => ['heredoc-string', ["<<<X\n\$a\nX", "<<<X\n\\\nX"], ["<<<X\n\$a[ ]\nX", "<<<X\n\\u{}\nX"]],
            'nowdoc' => ['nowdoc-string', ["<<<'X'\n\$a[ ] \\u{}\nX"], ["<<<'X'\nx\nY"]],
            'offsets' => ['double-quoted-string', ['"$a[key]"', '"$a[-1]"', '"$a[08]"', '"$a[0xF]"', '"$a[1_2]"'], ['"$a[ ]"', '"$a[1.0]"', '"$a[/*x*/0]"', '"$a[1__2]"', '"$a[0b2]"', '"$a[]"']],
            'varname' => ['double-quoted-string', ['"${var}"', '"${match}"', '"${var[0]}"', '"${$name}"'], ['"${var[0.5]}"' . 'x', '"${}"']],
            'property' => ['double-quoted-string', ['"$o->match"', '"$o?->p"', '"$o->9"', '"{$o->{"x"}}"'], ['"{$o->}"']],
        ];
    }

    /** Complete source expectations cross-checked in PHP parser mode, without execution. */
    public static function syntax(): array
    {
        $cases = [];
        foreach (['"', '`'] as $quote) {
            foreach (['$x', '{$x}', '${var}', '${match}', '${var[0]}', '${$name}', '$a[key]', '$a[0]', '$a[08]', '$a[-0xFF]', '$a[1_2]', '$a[$i]', '$o->p', '$o?->p', '$o->9', '$o-> p', '{$o->{"x"}}', '{$a["}"]}', '\\u{D800}', '\\u{10ffff}', '\\xZ', '\\777'] as $i => $body) {
                $cases['string-valid-' . bin2hex($quote) . '-' . $i] = ['<?php echo ' . $quote . $body . $quote . ';', true];
            }
            foreach (['$a[ ]', '$a[]', '$a[1.0]', '$a[0b2]', '$a[1__2]', '$a[/*x*/0]', '${}', '{$a + 1}', '{$o->}', '\\u{}', '\\u{110000}', '\\u{xyz}'] as $i => $body) {
                $cases['string-invalid-' . bin2hex($quote) . '-' . $i] = ['<?php echo ' . $quote . $body . $quote . ';', false];
            }
        }
        foreach ([false, true] as $nowdoc) foreach (["\n", "\r", "\r\n"] as $n => $nl) {
            $header = '<<<' . ($nowdoc ? "'X'" : '"X"') . $nl;
            foreach (['', '  ', "\t"] as $indent) {
                $name = ($nowdoc ? 'nowdoc' : 'heredoc') . '-' . $n . '-' . bin2hex($indent);
                $cases[$name] = ['<?php echo ' . $header . $indent . '$x' . $nl . $indent . 'X;', true];
                $cases[$name . '-blank'] = ['<?php echo ' . $header . $nl . $indent . 'body' . $nl . $indent . 'X;', true];
                if ($indent !== '') {
                    $cases[$name . '-blank-mixed'] = ['<?php echo ' . $header . ($indent[0] === ' ' ? "\t" : ' ') . $nl . $indent . 'X;', false];
                    $cases[$name . '-extra-indent'] = ['<?php echo ' . $header . $indent . " \tx" . $nl . $indent . 'X;', true];
                }
            }
        }
        $cases['heredoc-escape-before-label'] = ["<?php echo <<<X\n\\\nX;", true];
        $cases['nowdoc-unmatched-interpolation'] = ["<?php echo <<<'X'\n{\$bad \\u{}\nX;", true];
        $cases['heredoc-missing-label-byte'] = ["<?php echo <<<X\nX", false];
        $cases['nowdoc-missing-label-byte'] = ["<?php echo <<<'X'\nX", false];
        $cases['yield-close'] = ["<?php function f(){ yield // ?>\nfrom [1]; }", true];
        $cases['yield-nul'] = ["<?php function f(){ yield /*\0*/ from [1,2]; }", false];
        $cases['yield-nul-constant-offset'] = ["<?php function f(){ yield /*\0*/ from [1]; }", true];
        $cases['enum-prefix'] = ['<?php enum extendsName {}', false];
        $cases['enum-nul'] = ["<?php enum /*\0*/ A {}", false];
        $cases['real-parentheses'] = ['<?php echo (real);', false];
        $cases['real-cast'] = ['<?php echo (real) 1;', false];
        $cases['casts-deprecated'] = ['<?php (integer) 1; (double) 1; (boolean) 1; (binary) 1;', true];
        $cases['cast-void'] = ['<?php (void) 1;', true];
        $cases['halt-data'] = ["<?php __halt_compiler();\0\xff<?php garbage", true];
        $cases['halt-argument'] = ['<?php __halt_compiler(1);data', false];
        $cases['state-transitions'] = ["html<?php echo '?>'; echo \"\$x\"; echo `\$x`; echo <<<X\n\$x\nX; ?>tail<?= 1 ?>end", true];
        return $cases;
    }
}
