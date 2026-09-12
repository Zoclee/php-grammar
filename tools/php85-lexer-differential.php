<?php

declare(strict_types=1);

// Optional oracle only. Production recognition never loads this file.
require dirname(__DIR__) . '/vendor/autoload.php';

use PhpGrammar\Tests\Support\Php85LexicalCases;

if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 5) {
    fwrite(STDERR, "Run this command with PHP 8.5.x.\n");
    exit(2);
}

/** Collapse Zend's string-state tokens without consulting the repository lexer. */
function stringEnd(array $tokens, int $start): int
{
    $first = $tokens[$start];
    $stack = [is_array($first) ? 'heredoc' : substr($first, -1)];
    for ($i = $start + 1; $i < count($tokens); $i++) {
        $t = $tokens[$i];
        $top = $stack[array_key_last($stack)];
        if (is_array($t)) {
            if ($t[0] === T_START_HEREDOC) $stack[] = 'heredoc';
            elseif ($t[0] === T_END_HEREDOC) array_pop($stack);
            elseif (in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)) $stack[] = '}';
        } elseif ($t === $top) {
            array_pop($stack);
        } elseif ($top === '}') {
            if ($t === '{') $stack[] = '}';
            elseif (in_array(substr($t, -1), ['"', '`'], true)) $stack[] = substr($t, -1);
        }
        if ($stack === []) return $i;
    }
    throw new RuntimeException('Zend string has no closing token');
}

function normalized(string $source): array
{
    $prefix = [];
    // token_get_all starts in INITIAL; compile_file starts in SHEBANG.
    if (preg_match('/\A#![^\r\n]*(?:\r\n|\r|\n)/', $source, $shebang)) {
        $prefix[] = ['comment', $shebang[0]];
        $source = substr($source, strlen($shebang[0]));
    }
    $raw = token_get_all($source);
    $result = $prefix;
    $halt = false;
    foreach ($raw as $i => $unused) {
        if ($i < ($next ?? 0)) continue;
        $t = $raw[$i];
        if (!is_array($t)) {
            if (in_array(substr($t, -1), ['"', '`'], true)) {
                $end = stringEnd($raw, $i);
                $text = '';
                for ($j = $i; $j <= $end; $j++) $text .= is_array($raw[$j]) ? $raw[$j][1] : $raw[$j];
                $result[] = [substr($t, -1) === '`' ? 'backtick-string' : 'string-literal', $text];
                $next = $end + 1;
            } else $result[] = [str_contains(';:,()[]{}\\$', $t) ? 'punctuation' : 'operator', $t];
            continue;
        }
        [$id, $text] = $t;
        if ($id === T_START_HEREDOC) {
            $type = str_contains($text, "'") ? 'nowdoc-string' : 'heredoc-string';
            $end = stringEnd($raw, $i);
            for ($j = $i + 1; $j <= $end; $j++) $text .= is_array($raw[$j]) ? $raw[$j][1] : $raw[$j];
            $result[] = [$type, $text];
            $next = $end + 1;
            continue;
        }
        if ($id === T_OPEN_TAG) {
            $size = str_starts_with(strtolower($text), '<?php') ? 5 : 2;
            $result[] = ['open-tag', substr($text, 0, $size)];
            if (strlen($text) > $size) $result[] = ['whitespace', substr($text, $size)];
            continue;
        }
        if ($id === T_YIELD_FROM) {
            $result[] = ['keyword', substr($text, 0, 5)];
            $trivia = substr($text, 5, -4);
            preg_match_all('~[ \t\r\n]+|/\*.*?\*/|//[^\r\n]*[\r\n]|\#[^\r\n]*[\r\n]~s', $trivia, $parts);
            if (implode('', $parts[0]) !== $trivia) throw new RuntimeException('Unmapped composite trivia');
            foreach ($parts[0] as $part) $result[] = [str_contains(" \t\r\n", $part[0]) ? 'whitespace' : (preg_match('~^/\*\*[ \t\r\n]~', $part) ? 'doc-comment' : 'comment'), $part];
            $result[] = ['keyword', substr($text, -4)];
            continue;
        }
        // Recovery tokens for removed casts do not mean PHP 8.5 accepts a cast.
        if ($id === T_UNSET_CAST || ($id === T_DOUBLE_CAST && preg_match('/real/i', $text))) {
            preg_match('/^(\()([ \t]*)(\w+)([ \t]*)(\))$/', $text, $m);
            $result[] = ['punctuation', '('];
            if ($m[2] !== '') $result[] = ['whitespace', $m[2]];
            $result[] = [$id === T_UNSET_CAST ? 'keyword' : 'identifier', $m[3]];
            if ($m[4] !== '') $result[] = ['whitespace', $m[4]];
            $result[] = ['punctuation', ')'];
            continue;
        }
        $type = match ($id) {
            T_OPEN_TAG_WITH_ECHO => 'echo-open-tag', T_CLOSE_TAG => 'close-tag',
            T_WHITESPACE => 'whitespace', T_COMMENT => 'comment', T_DOC_COMMENT => 'doc-comment',
            T_INLINE_HTML => $halt ? 'halt-compiler-data' : 'inline-html',
            T_STRING => 'identifier', T_VARIABLE => 'variable',
            T_NAME_QUALIFIED => 'qualified-name', T_NAME_FULLY_QUALIFIED => 'fully-qualified-name', T_NAME_RELATIVE => 'relative-name',
            T_LNUMBER => 'integer-literal',
            T_DNUMBER => preg_match('/^0[xbo]/i', $text) || !preg_match('/[.eE]/', $text) ? 'integer-literal' : 'floating-literal',
            T_CONSTANT_ENCAPSED_STRING => 'string-literal', T_ATTRIBUTE, T_NS_SEPARATOR => 'punctuation',
            T_BAD_CHARACTER => throw new RuntimeException('Zend bad character'),
            default => preg_match('/^[a-zA-Z_][a-zA-Z_]*$/D', $text) ? 'keyword' : 'operator',
        };
        if ($id === T_HALT_COMPILER) $halt = true;
        $result[] = [$type, $text];
    }
    $merged = [];
    foreach ($result as $t) {
        $last = array_key_last($merged);
        if ($t[0] === 'whitespace' && $last !== null && $merged[$last][0] === 'whitespace') $merged[$last][1] .= $t[1];
        else $merged[] = $t;
    }
    return $merged;
}

$failures = [];
$diagnostics = [];
set_error_handler(static function (int $severity, string $message) use (&$diagnostics): bool {
    if (!in_array($severity, [E_DEPRECATED, E_COMPILE_WARNING], true)) return false;
    $diagnostics[$message] = ($diagnostics[$message] ?? 0) + 1;
    return true;
});
$counts = ['tokens' => 0, 'lexical_rejections' => 0, 'syntax' => 0, 'corpus_tokens' => 0, 'documented_oracle_exceptions' => 0];
$exceptions = [
    'halt-malformed' => 'token_get_all halts after three significant tokens; parser requires the actual directive syntax.',
];
// PHP_INI_PERDIR: short_open_tag cannot be switched with ini_set. Run both profiles.
$short = (bool) ini_get('short_open_tag');
foreach (Php85LexicalCases::all() as $name => $case) {
    if ($case['short'] !== $short) continue;
    if (isset($exceptions[$name])) { $counts['documented_oracle_exceptions']++; continue; }
    try {
        if (isset($case['error'])) {
            // TOKEN_PARSE supplies parser-mode errors where tokenizer recovery differs.
            try { token_get_all($case['source'], TOKEN_PARSE); }
            catch (ParseError) { $counts['lexical_rejections']++; continue; }
            throw new RuntimeException('Zend accepted lexical rejection');
        }
        $actual = normalized($case['source']);
        if ($actual !== $case['tokens']) throw new RuntimeException('Tokens differ: ' . var_export($actual, true));
        $counts['tokens']++;
    } catch (Throwable $e) { $failures[$name] = $e->getMessage(); }
}
if ($short) foreach (Php85LexicalCases::syntax() as $name => [$source, $expected]) {
    try { token_get_all($source, TOKEN_PARSE); $accepted = true; }
    catch (ParseError) { $accepted = false; }
    $counts['syntax']++;
    if ($accepted !== $expected) $failures['syntax:' . $name] = 'Parser-mode acceptance differs';
}
// Broader integration cross-check; unlike the matrix above, expected tokens here
// come from the separately implemented Zend adapter and are not independent data.
foreach (glob(dirname(__DIR__) . '/tests/fixtures/php/8.5/' . ($short ? '' : 'short-tags-disabled/') . 'valid/*.php') as $file) {
    $name = basename($file);
    try {
        $source = file_get_contents($file);
        $zend = normalized($source);
        $repository = array_map(static fn ($t) => [$t->type->value, $t->lexeme],
            \PhpGrammar\Php\Lexing\Lexer::forPhp85()->withShortOpenTag($short)->tokenize($source)->all());
        if ($zend !== $repository) throw new RuntimeException('Zend/repository token streams differ');
        $counts['corpus_tokens']++;
    } catch (Throwable $e) { $failures['corpus:' . $name] = $e->getMessage(); }
}
echo json_encode(['php' => PHP_VERSION, 'short_open_tag' => $short, 'comparisons' => $counts,
    'expected_diagnostic_count' => array_sum($diagnostics), 'unexpected_mismatches' => count($failures)], JSON_PRETTY_PRINT), "\n";
foreach ($failures as $name => $failure) fwrite(STDERR, "$name: $failure\n");
exit($failures === [] ? 0 : 1);
