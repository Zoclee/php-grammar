<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Support;

use RuntimeException;

/** Explicit dispositions tied to the pinned scanner inventory, not code coverage. */
final class Php85LexicalAudit
{
    public static function report(string $root): array
    {
        $inventory = json_decode(file_get_contents($root . '/docs/8.5/source-inventory.json'), true, flags: JSON_THROW_ON_ERROR);
        $coverage = json_decode(file_get_contents($root . '/docs/8.5/phase3-coverage.json'), true, flags: JSON_THROW_ON_ERROR);
        $families = [];
        $definitions = [
            'tags' => ['represented-differently', 'Lexer::tokenize/consumeOpenTag', 'Opening delimiter excludes following whitespace; closing delimiter includes one newline. Both short-tag profiles.'],
            'inline-html' => ['verified', 'Lexer::tokenize', 'Only recognized openers enter scripting; binary HTML is preserved.'],
            'shebang' => ['represented-differently', 'Lexer::tokenize', 'compile_file SHEBANG handling becomes a retained comment; tokenizer INITIAL needs oracle normalization.'],
            'whitespace' => ['fixed', 'Lexer::isWhitespace; LexerState::consume', 'Exactly SP/TAB/CR/LF. CRLF counts once across token consumption boundaries; byte columns are a project API.'],
            'comments' => ['represented-differently', 'Lexer::consumeLineComment/consumeBlockComment', 'Doc classification requires ordinary whitespace after /**. Unterminated comments throw instead of recovery tokens. Trivia is removed before parsing.'],
            'identifiers' => ['verified', 'Lexer::consumeIdentifierOrKeyword/isIdentifierStart/isIdentifierPart', 'ASCII letters/underscore plus every byte 80-FF; digits are continuation bytes.'],
            'keywords' => ['represented-differently', 'PhpVersion::php85; Lexer::consumeIdentifierOrKeyword; PhpGrammarMatcher::semiReservedIdentifierMatcher', 'Case-insensitive scanner keywords use one category; grammar primitives separately constrain identifier legality.'],
            'enum' => ['fixed', 'Lexer::consumeIdentifierOrKeyword; LOOKAHEAD_TRIVIA', 'Longer extends/implements lookahead excludes prefixes too. NUL is excluded from lookahead comments.'],
            'yield-from' => ['fixed', 'Lexer::tokenize; LOOKAHEAD_TRIVIA', 'Atomic Zend token is split into two keywords plus retained trivia. A close tag inside a composite lookahead comment does not leave scripting.'],
            'variables' => ['verified', 'Lexer::consumeVariable', 'Dollar plus LABEL is atomic; extra dollar/braces are separate punctuation even in parser-invalid sequences.'],
            'property' => ['represented-differently', 'Lexer::consumePhpToken; lookingForProperty flag; StringSyntax::fragments', 'LABEL after object operators is an identifier even for keywords. Whitespace/comments retain the state; fallback resumes scripting.'],
            'names' => ['verified', 'Lexer::consumePhpToken qualified-name patterns', 'Qualified, fully qualified and namespace-relative names are atomic; separators cannot contain trivia.'],
            'integers' => ['represented-differently', 'Lexer::consumeNumber; PhpGrammarMatcher::lexicalPrimitiveMatchers', 'Spelling determines integer category independently of machine overflow. Legacy octal 8/9 rejection is performed by numeric primitives.'],
            'floats' => ['verified', 'Lexer::consumeNumber', 'DNUM/EXPONENT_DNUM maximal munch, fraction/exponent separators and dot/ellipsis boundaries. Values/overflow are not lexical correctness.'],
            'operators' => ['represented-differently', 'Lexer::OPERATORS/PUNCTUATION/consumePhpToken; EBNF reference-marker', 'Longest spelling wins. Ampersand token subcategories collapse; reference placement and delimiter balance are syntactic.'],
            'casts' => ['fixed', 'Lexer::consumePhpToken; PhpGrammarInput::valueAt', 'SP/TAB only; canonical and deprecated aliases. Removed real throws in parser mode, unset remains separate rejected syntax. Zend emits T_UNSET_CAST; false && (unset) 1 is a recorded Phase 6 folding discrepancy.'],
            'single-strings' => ['represented-differently', 'Lexer::consumeQuotedString', 'One source token, byte escapes retained without value conversion. Unterminated strings throw rather than expose recovery tokens.'],
            'double-strings' => ['represented-differently', 'Lexer::consumeQuotedString; StringSyntax::fragments', 'Aggregate complete source span; scripting/interpolation fragments are checked separately by EBNF.'],
            'backticks' => ['represented-differently', 'Lexer::consumeQuotedString; StringSyntax::fragments', 'Own delimiter with double-string interpolation/escape recognition; commands are never executed by tests.'],
            'escapes' => ['represented-differently', 'StringSyntax::fragments; Lexer::consumeQuotedString', 'Escape bytes are retained. Invalid Unicode escapes fail StringSyntax; conversion values and deprecation delivery are outside scope.'],
            'interpolation' => ['represented-differently', 'Lexer::interpolationEnd; StringSyntax::fragments; PhpGrammarMatcher::matchesRule', 'Recursive scripting braces, varname/property/offset states; aggregate outer strings. Deprecated dollar-brace forms remain valid. Expression legality belongs to EBNF.'],
            'heredoc' => ['fixed', 'Lexer::consumeHereString/interpolationEnd; PhpGrammarMatcher::tokenizeForRule', 'Case-sensitive labels require a following non-LABEL byte, including empty bodies. Nested labels and indentation enforced; fragments receive a newline sentinel, whole files preserve EOF.'],
            'nowdoc' => ['fixed', 'Lexer::consumeHereString', 'Separate literal body with no interpolation/escape validation. Same exact label boundary and indentation constraints.'],
            'halt' => ['represented-differently', 'Lexer::tokenize; halt-compiler-data primitive', 'Recognized directive makes all trailing bytes opaque; syntax/scope legality is checked by grammar/contextual layer. Tokenizer recovery for malformed directives is not authoritative acceptance.'],
            'bytes' => ['represented-differently', 'LexerState; byte LABEL predicates; StringInput/default code-unit primitive', 'Raw PHP strings, no UTF-8 decoding. Invalid scripting bytes throw instead of T_BAD_CHARACTER. Encoding conversion filters are outside the raw-byte source profile.'],
        ];
        foreach ($definitions as $name => [$status, $implementation, $decision]) {
            $families[$name] = compact('status', 'implementation', 'decision') + ['positive' => [], 'boundary' => []];
        }
        foreach (Php85LexicalCases::all() as $id => $case) $families[$case['family']][$case['boundary'] ? 'boundary' : 'positive'][] = $id;
        // Family-wide boundary witnesses may consist of parser/primitive rejection,
        // rather than expecting a source-span lexer to reject an entire string.
        $primitiveFamilies = ['identifiers' => 'identifiers', 'name-identifiers' => 'identifiers', 'semi-reserved' => 'keywords',
            'variables' => 'variables', 'qualified' => 'names', 'fully-qualified' => 'names', 'relative' => 'names',
            'decimal' => 'integers', 'binary' => 'integers', 'octal' => 'integers', 'explicit-octal' => 'integers', 'hex' => 'integers',
            'float' => 'floats', 'single' => 'single-strings', 'double' => 'escapes', 'constant-double' => 'double-strings',
            'constant-string' => 'nowdoc', 'string' => 'interpolation', 'backticks' => 'backticks', 'heredoc' => 'heredoc',
            'nowdoc' => 'nowdoc', 'offsets' => 'interpolation', 'varname' => 'interpolation', 'property' => 'property'];
        foreach (Php85LexicalCases::primitives() as $id => [$rule, $positive, $negative]) {
            $families[$primitiveFamilies[$id]]['primitive_evidence'][$id] = compact('rule') + ['positive_count' => count($positive), 'negative_count' => count($negative)];
        }
        $lineGroups = [
            'comments' => [1421, 2458, 2482], 'yield-from' => [1426], 'enum' => [1568, 1572],
            'property' => [1585, 1590, 1599, 1603, 1607, 2395, 2401, 2518], 'whitespace' => [1595],
            'operators' => [1612, 1616, 1620, 1784, 1788, 1792, 1808, 1824, 1828, 1832, 1836, 1840, 1844, 1848, 1852, 1856, 1860, 1864, 1868, 1872, 1876, 1880, 1884, 1888, 1892, 1896, 1900, 1904, 1908, 1912, 1916, 1920, 1924, 1940, 1944, 1948, 1953, 1957, 1962, 1967, 1972, 1985],
            'casts' => [1636, 1640, 1650, 1654, 1664, 1672, 1676, 1686, 1690, 1694, 1698, 1708, 1712],
            'halt' => [1760], 'interpolation' => [1979, 1994, 2002, 2206, 2222, 2409, 2419, 2424, 2429, 2843],
            'integers' => [2009, 2051, 2101, 2164], 'floats' => [2231], 'shebang' => [2287, 2293],
            'tags' => [2299, 2309, 2315, 2330, 2524], 'inline-html' => [2339], 'variables' => [2415],
            'names' => [2437, 2441, 2445, 2449], 'identifiers' => [2453], 'single-strings' => [2536],
            'double-strings' => [2630, 2851, 2862], 'heredoc' => [2676, 2829, 2962],
            'backticks' => [2823, 2856, 2916], 'nowdoc' => [3086], 'bytes' => [3177],
        ];
        $byLine = [];
        foreach ($lineGroups as $family => $lines) foreach ($lines as $line) $byLine[$line] = $family;
        $rules = [];
        foreach ($inventory['files']['zend_language_scanner.l']['entries'] as $entry) {
            $family = $byLine[$entry['line']] ?? null;
            if ($family === null && preg_match('/^<ST_IN_SCRIPTING>"([a-zA-Z_]+)"$/D', $entry['rule'])) $family = 'keywords';
            if ($family === null) throw new RuntimeException('Unreviewed scanner rule: ' . $entry['line']);
            preg_match('/^<([^>]+)>/', $entry['rule'], $states);
            $info = $families[$family];
            $rules[] = $entry + ['family' => $family, 'states' => explode(',', $states[1]),
                'status' => $info['status'], 'implementation' => $info['implementation'],
                'evidence' => 'families/' . $family, 'disposition' => $info['decision']];
        }
        $productionGroups = [
            'identifiers' => 'ascii-letter identifier identifier-part identifier-start identifier-start-character name-identifier',
            'bytes' => 'non-ascii-byte source-character',
            'keywords' => 'semi-reserved-identifier', 'variables' => 'variable',
            'names' => 'qualified-name fully-qualified-name namespace-relative-name',
            'integers' => 'binary-digit binary-integer-literal decimal-digit decimal-digit-nonzero decimal-digits decimal-integer-literal explicit-octal-integer-literal hexadecimal-digit hexadecimal-integer-literal numeric-separator octal-digit octal-integer-literal',
            'floats' => 'exponent-marker exponent-part floating-literal',
            'single-strings' => 'single-quoted-string single-quoted-string-character single-quoted-string-content',
            'double-strings' => 'double-quoted-string string-character string-literal string-text',
            'interpolation' => 'encapsulated-offset encapsulated-string-part encapsulated-variable numeric-string',
            'escapes' => 'escape-sequence', 'backticks' => 'backtick-string backtick-string-part-list',
            'heredoc' => 'heredoc-body heredoc-label heredoc-string', 'nowdoc' => 'nowdoc-body nowdoc-character nowdoc-string',
            'inline-html' => 'inline-html-character inline-html-text', 'halt' => 'halt-compiler-data',
            'whitespace' => 'newline whitespace whitespace-character',
            'comments' => 'block-comment block-comment-character block-comment-text comment doc-comment doc-comment-text line-comment line-comment-character line-comment-text',
        ];
        $byProduction = [];
        foreach ($productionGroups as $family => $names) foreach (explode(' ', $names) as $name) $byProduction[$name] = $family;
        $bypasses = $contextual = $scannerContextual = [];
        foreach ($coverage['remaining'] as $entry) {
            if ($entry['classification'] === 'contextual-only') { $contextual[] = $entry; continue; }
            if ($entry['classification'] === 'scanner-context-only') { $scannerContextual[] = $entry; continue; }
            if (!in_array($entry['classification'], ['primitive-bypassed', 'trivia-removed'], true)) throw new RuntimeException('Unclassified grammar coverage gap');
            $production = explode('/', $entry['identity'])[0];
            $family = $byProduction[$production] ?? throw new RuntimeException('Unmapped lexical production: ' . $production);
            $bypasses[] = array_intersect_key($entry, array_flip(['kind', 'identity', 'classification'])) + ['family' => $family, 'evidence' => 'families/' . $family];
        }
        $inputs = ['src/Php/Lexing/Lexer.php', 'src/Php/Lexing/LexerState.php', 'src/Php/Lexing/PhpVersion.php',
            'src/Php/Lexing/StringSyntax.php', 'src/Php/Conformance/PhpGrammarMatcher.php', 'src/Php/Conformance/PhpGrammarInput.php', 'tests/Support/Php85LexicalCases.php',
            'tests/Support/Php85LexicalAudit.php', 'tools/php85-lexer-differential.php', 'docs/8.5/source-inventory.json',
            'docs/8.5/phase3-coverage.json', 'grammar/8.5/php.ebnf'];
        $hashes = [];
        foreach ($inputs as $file) $hashes[$file] = hash_file('sha256', $root . '/' . $file);
        return ['revision' => $inventory['revision'], 'scanner_sha256' => $inventory['files']['zend_language_scanner.l']['sha256'],
            'method' => 'Source rule dispositions plus independent token/byte and primitive matrices. Status is scoped evidence, not an exhaustive equivalence percentage.',
            'input_hashes' => $hashes, 'families' => $families, 'rules' => $rules, 'grammar_bypasses' => $bypasses,
            'contextual_only' => $contextual,
            'scanner_context_only' => $scannerContextual,
            'syntax_evidence' => array_keys(Php85LexicalCases::syntax()),
            'supporting_source' => [
                'zend_language_scanner.l:300-319 zend_lex_tstring' => 'Parser identifier feedback is separate from scanning; semi-reserved primitives and existing declaration fixtures.',
                'zend_language_scanner.l:322-524,879-910 encoding filters' => 'Outside raw-byte profile; no Unicode decoding is promised.',
                'zend_language_scanner.l:928-1148 zend_scan_escape_string' => 'escapes family; StringSyntax Unicode validity; value conversion intentionally omitted.',
                'zend_language_scanner.l:1153-1240 newline/indentation helpers' => 'whitespace, heredoc, nowdoc families plus syntax-evidence blank/mixed/extra-indentation cases.',
                'zend_language_scanner.l:1254-1357 nesting and EOF diagnostics' => 'Braced interpolation boundary scanner plus EBNF delimiter nesting; recovery diagnostics not reproduced.',
                'zend_language_scanner.l:1371-1392 macros' => 'Byte/digit/operator matrices; NUL-sensitive enum/yield lookahead; 190 state rules below.',
                'zend_language_parser.y:407-410 halt directive' => 'halt family and malformed-argument syntax case; no return to source scanning after valid directive.',
                'zend_language_parser.y:596,859-894 ampersand contexts' => 'Both scanner ampersands mapped to &, EBNF parameter/reference/intersection fixtures remain mandatory.',
                'zend_language_parser.y:1368; zend_compile.c:10472,12366 unset cast' => 'Removed live cast policy preserved; short-circuit folding discrepancy explicitly recorded for Phase 6.',
            ],
            'outside_lexical_profile' => [
                'zend.multibyte input/output encoding conversion and BOM filtering: callers provide already-selected raw source bytes.',
                'Scanner token values, interning, memory limits, event callbacks, diagnostic wording and deprecation delivery.',
                'Delimiter nesting diagnostics and expression legality: EBNF/parser; compiler halt scope and return-only type placements: contextual.',
            ],
            'unproven' => [
                'All possible recursively nested interpolation/heredoc/state combinations; finite deterministic matrices and existing nested corpus are evidence, not a formal proof.',
                'Exact Zend recovery token stream on malformed input; this lexer throws or aggregates invalid string contents for later primitive/EBNF rejection.',
                'AST, contextual legality, constant folding and full language conformance remain Phases 6-7.',
            ]];
    }
}
