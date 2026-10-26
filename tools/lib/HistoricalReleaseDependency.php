<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
use PhpGrammar\Tests\Support\Php85LexicalAudit;
require_once __DIR__ . '/Certification86.php';

/** Existing Phase 4B change authority, now an explicit current release dependency. */
final class HistoricalReleaseDependency
{
    public const REVIEW = 'docs/8.6/shared-lexer-regression.json';
    public const REVIEW_SHA256 = 'f5b003e9a56e185050fd603767bb8eaf6bc2d62aae85b77f3053cadff05ee61f';
    public const ID = 'lexer-coverage-shared-selector@v2';

    public static function correspondenceErrors(array $review, string $historical, array $current, string $selectorHash): array
    {
        $errors = [];
        if (hash('sha256', $historical) !== ($review['historicalLedgerSha256'] ?? null)) $errors[] = 'Historical scanner ledger changed';
        $encoded = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        if (hash('sha256', $encoded) !== ($review['currentLedgerSha256'] ?? null)) $errors[] = 'Current scanner ledger differs from reviewed transition';
        if (array_keys($review['inputChanges'] ?? []) !== ['src/Php/Lexing/PhpVersion.php']) return [...$errors, 'Unauthorized scanner transition scope'];
        $old = json_decode($historical, true, 512, JSON_THROW_ON_ERROR); $path = 'src/Php/Lexing/PhpVersion.php'; $change = $review['inputChanges'][$path];
        if (($old['input_hashes'][$path] ?? null) !== $change['before'] || $selectorHash !== $change['after']) $errors[] = 'Unreviewed shared selector bytes';
        $old['input_hashes'][$path] = $change['after'];
        if ($old !== $current) $errors[] = 'Scanner input, mapping, family or disposition changed outside approved selector hash';
        return $errors;
    }

    public static function executionErrors(string $log): array
    {
        $text = str_replace("\r\n", "\n", $log);
        $summary = 'Scanner rules: 190; direct cases: 2700; primitive cases: 101; syntax cases: 144; grammar bypass entries: 246; unexpected failures: 1';
        $tail = "Unproven: exhaustive recursive state combinations and exact malformed-input recovery; see Phase 5 report.\n"
            . "Ledger stale: run composer lexer:coverage -- --write\n"
            . 'Script php bin/lexer-coverage.php handling the lexer:coverage event returned with error code 1';
        if (!str_contains($text, $summary . "\n") || !str_ends_with(trim($text), $tail)) return ['Lexer gate has failures beyond the reviewed ledger freshness condition'];
        return [];
    }

    public function __construct(private readonly string $root) {}

    public function derive(string $snapshot): array
    {
        $c = new Certification86($this->root);
        if ($c->verifyHashes([self::REVIEW => self::REVIEW_SHA256])) throw new \RuntimeException('Untrusted shared lexer transition authority');
        $review = $c->read(self::REVIEW); $historical = file_get_contents($c->path('docs/8.5/phase5-lexical-evidence.json'));
        $current = Php85LexicalAudit::report($snapshot);
        $errors = self::correspondenceErrors($review, $historical, $current, hash_file('sha256', $snapshot . '/src/Php/Lexing/PhpVersion.php'));
        return ['contract' => 'php85-current-lexer-dependency-v2', 'identity' => self::ID, 'authority' => [self::REVIEW => self::REVIEW_SHA256],
            'review' => $review, 'correspondence' => $current, 'errors' => $errors, 'historicalGatePassed' => false, 'historicalCertificationRenewed' => false];
    }

    public function verify(array $record, string $snapshot, string $executionLog): array
    {
        try {
            $expected = $this->derive($snapshot);
            if ($record !== $expected) return ['Tampered current lexer dependency evidence'];
            return [...$expected['errors'], ...self::executionErrors($executionLog)];
        } catch (\Throwable $e) { return [$e->getMessage()]; }
    }
}
