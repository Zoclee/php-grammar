<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

/** Stage-specific research helpers; no language-validity or release inference. */
final class ExactPinReconciliation
{
    public static function resultErrors(array $actual, array $expected): array
    {
        $errors = [];
        if (($actual['exit'] ?? null) !== $expected['exit']) $errors[] = 'exit';
        if (isset($expected['contains']) && !str_contains(($actual['stdout'] ?? '') . ($actual['stderr'] ?? ''), $expected['contains'])) $errors[] = 'diagnostic';
        if (isset($expected['stdout'])) {
            if (($actual['stdout'] ?? null) !== $expected['stdout']) $errors[] = 'stdout';
            if (($actual['stderr'] ?? null) !== '') $errors[] = 'stderr';
        }
        return $errors;
    }

    /** Preserve raw operands. This is a display parser, not an AST/VM validator. */
    public static function opcodeLines(string $text): array
    {
        preg_match_all('/^\d{4} .+$/m', str_replace("\r\n", "\n", $text), $matches);
        return $matches[0];
    }

    public static function stageSummary(array $lint, ?array $execution, array $dump): array
    {
        return ['lintAccepted' => $lint['exit'] === 0,
            'executionObserved' => $execution !== null,
            'executionSucceeded' => $execution === null ? null : $execution['exit'] === 0,
            'opcodeDumpObserved' => self::opcodeLines($dump['stdout'] . $dump['stderr']) !== [],
            'independentZendParserVerdict' => false, 'intendedLanguageValidityEstablished' => false];
    }
}
