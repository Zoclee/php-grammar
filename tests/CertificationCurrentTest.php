<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\Certification86 as C;
use PhpGrammar\Tools\CertificationCurrent as E;
use PhpGrammar\Tools\CertificationCurrentRun as R;
use PhpGrammar\Tools\Preservation86 as P;
use PhpGrammar\Tools\HistoricalReleaseDependency as H;
require_once dirname(__DIR__) . '/tools/lib/Support.php';
require_once dirname(__DIR__) . '/tools/lib/CertificationCurrentRun.php';

final class CertificationCurrentTest extends TestCase
{
    private string $temporary;
    protected function setUp(): void
    {
        $this->temporary = str_replace('\\', '/', sys_get_temp_dir()) . '/php-grammar-current-' . bin2hex(random_bytes(8));
        mkdir($this->temporary);
    }
    protected function tearDown(): void
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temporary, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) { if ($file->isDir()) rmdir($file->getPathname()); else unlink($file->getPathname()); }
        rmdir($this->temporary);
    }
    private function put(string $path, string $bytes): void
    {
        $file = $this->temporary . '/' . $path; if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true); file_put_contents($file, $bytes);
    }
    private function authority(): P
    {
        foreach (array_keys(P::ANCHORS) as $path) $this->put($path, file_get_contents(dirname(__DIR__) . '/' . $path));
        return new P($this->temporary);
    }
    public function testUnauthorizedBaselineBytesAreRejected(): void
    {
        $p = $this->authority(); $t = $p->derive();
        $path = 'grammar/8.6/php.ebnf'; $this->put($path, 'unauthorized grammar');
        self::assertContains('Stale evidence digest: ' . $path, $p->verify($t));
    }
    public function testIncorrectTransitionCannotBroadenAuthorization(): void
    {
        $p = $this->authority(); $t = $p->derive(); $t['changes']['grammar/8.6/php.ebnf'] = ['old', 'new'];
        self::assertSame(['Incorrect or tampered preservation transition'], $p->verify($t));
    }
    public function testMissingPredecessorFailsClosed(): void
    {
        $p = $this->authority(); $t = $p->derive(); unlink($this->temporary . '/docs/8.6/certification-baseline.json');
        self::assertStringContainsString('Missing evidence artifact', implode(';', $p->verify($t)));
    }
    public function testTamperedSuccessorCannotSupplyItsOwnExpectedHashes(): void
    {
        $p = $this->authority(); $t = $p->derive(); $t['baselines']['docs/8.6/certification-baseline.json']['successor']['docs/api.md'] = hash('sha256', 'changed');
        self::assertSame(['Incorrect or tampered preservation transition'], $p->verify($t));
    }
    public function testChangedSharedPolicyIsRejected(): void
    {
        $p = $this->authority(); $t = $p->derive(); $this->put('docs/api.md', 'changed policy');
        self::assertContains('Stale evidence digest: docs/api.md', $p->verify($t));
    }
    public static function auditMutations(): iterable
    {
        yield 'stale source-audit dependencies' => ['inputs'];
        yield 'missing parser correspondence' => ['parser'];
        yield 'missing scanner correspondence' => ['scanner'];
        yield 'incorrect RFC traceability' => ['rfcs'];
        yield 'modified source lock' => ['sourceLockVerification'];
    }
    #[DataProvider('auditMutations')]
    public function testAuditRejectsIncompleteOrModifiedCorrespondence(string $field): void
    {
        // Whole record equality is required, not matching headline counts.
        $original = ['parser' => ['rows' => ['expr' => ['branches' => ['a', 'b']]]], 'scanner' => ['rules' => ['S86-001']],
            'rfcs' => [['slug' => 'reviewed', 'disposition' => 'unresolved']], 'sourceLockVerification' => ['source' => str_repeat('a', 64)]];
        $inputs = ['tools/audit.php' => str_repeat('b', 64)];
        $audit = ['contract' => 'php86-current-source-audit-v2', 'inputs' => $inputs, 'snapshot' => $this->temporary,
            'projection' => P::CHANGES, 'correspondence' => $original, 'certificationGranted' => false];
        self::assertSame([], R::auditErrors($audit, $inputs, $this->temporary, $original));
        if ($field === 'inputs') $audit['inputs'] = []; else unset($audit['correspondence'][$field]);
        self::assertNotEmpty(R::auditErrors($audit, $inputs, $this->temporary, $original));
    }
    public function testIncorrectExecutableIdentityIsRejectedBeforeExecution(): void
    {
        $this->put('fake.exe', 'not PHP');
        $errors = E::profileErrors(['profile' => 'exact-pin-rc4', 'legacyProfile' => 'historical-rc4', 'exact' => $this->temporary . '/fake.exe', 'php86' => $this->temporary . '/fake.exe']);
        self::assertContains('Incorrect executable identity: exact-pin-rc4', $errors);
    }
    public function testWrongContextualProfileIsRejected(): void
    {
        self::assertContains('Explicit exact and legacy profile selection required', E::profileErrors(['profile' => 'historical-rc4', 'legacyProfile' => 'historical-rc4']));
    }
    public function testTamperedCompatibilityArtifactFailsDigestVerification(): void
    {
        $this->put('compatibility/evidence.json', '{"complete":true}'); $c = new C($this->temporary); $hashes = $c->hashes(['compatibility/evidence.json']);
        self::assertSame([], $c->verifyHashes($hashes));
        $this->put('compatibility/evidence.json', '{"complete":false}');
        self::assertSame(['Stale evidence digest: compatibility/evidence.json'], $c->verifyHashes($hashes));
    }
    private function assessment(): array
    {
        $legacy = []; foreach (C::gates() as $id => [$kind, $obligation]) $legacy[$id] = ['obligation' => $obligation, 'status' => 'not-executed', 'reason' => 'Isolated structural test'];
        $checks = array_fill_keys(E::CHECKS, true); $blockers = [];
        foreach (C::BLOCKERS as $id) $blockers[] = ['id' => $id, 'blocksCertification' => true, 'remainingRequirements' => ['Unresolved evidence']];
        $r = ['contract' => E::CONTRACT, 'evidenceKind' => 'current-assessment', 'certificationGranted' => false, 'grammarComplete' => false,
            'wholeSourceValidatorComplete' => false, 'historicalCertificationRenewed' => false, 'checks' => $checks, 'blockers' => $blockers,
            'gates' => E::gates($legacy, $checks, $blockers), 'prerequisites' => ['historical-artifact-integrity85' => true, 'current-compatibility85' => true]];
        return [$r, $legacy, $checks, $blockers];
    }
    public static function gateMutations(): iterable
    {
        yield 'missing required release gate' => ['missing'];
        yield 'incorrect dependency' => ['dependency'];
        yield 'falsified execution status' => ['execution'];
        yield 'static blocker removal without evidence' => ['blocker'];
        yield 'unsupported certification claim' => ['claim'];
        yield 'historical evidence labeled current' => ['historical'];
    }
    #[DataProvider('gateMutations')]
    public function testAssessmentRejectsUnverifiedGateChanges(string $mutation): void
    {
        [$r, $legacy, $checks, $blockers] = $this->assessment();
        self::assertSame([], E::assessmentErrors($r, $legacy, $checks, $blockers));
        switch ($mutation) {
            case 'missing': unset($r['gates']['source-lock-integrity']); break;
            case 'dependency': $r['gates']['historical-certification-check']['requiredInputs'] = ['current-compatibility']; break;
            case 'execution': $r['gates']['folding-order']['execution'] = 'executed'; $r['gates']['folding-order']['result'] = 'passed'; break;
            case 'blocker': array_pop($r['blockers']); break;
            case 'claim': $r['certificationGranted'] = true; break;
            case 'historical': $r['evidenceKind'] = 'historical-certification'; break;
        }
        self::assertNotEmpty(E::assessmentErrors($r, $legacy, $checks, $blockers));
    }
    public function testReadOnlyInvalidEvidenceCheckPreservesArtifacts(): void
    {
        $this->put('invalid.json', '{}'); $this->put('sentinel', 'unchanged');
        $before = (new C($this->temporary))->hashes(['invalid.json', 'sentinel']);
        self::assertNotEmpty((new R($this->temporary))->verify('invalid.json'));
        self::assertSame([], (new C($this->temporary))->verifyHashes($before));
        self::assertCount(2, S::files($this->temporary));
    }
    public function testHistoricalDependencyRequiresEveryReleaseObligation(): void
    {
        $rows = []; foreach (R::release85Ids() as $id) $rows[] = ['gate' => $id, 'exit_code' => in_array($id, ['final-certification', 'lexer-coverage'], true) ? 1 : 0];
        $message = 'Stale certification artifact: docs/8.5/final-evidence.json';
        self::assertSame([], R::release85Errors($rows, $message, $this->lexerLog()));
        $rows[9]['exit_code'] = 1;
        self::assertContains('Unmet PHP 8.5 obligation: explicit-ast', R::release85Errors($rows, $message, $this->lexerLog()));
        array_pop($rows); self::assertNotEmpty(R::release85Errors($rows, $message, $this->lexerLog()));
    }
    public function testUnexplainedHistoricalFailureCannotBecomeCompatibilityPass(): void
    {
        $rows = []; foreach (R::release85Ids() as $id) $rows[] = ['gate' => $id, 'exit_code' => in_array($id, ['final-certification', 'lexer-coverage'], true) ? 1 : 0];
        self::assertContains('Historical aggregate has an unexplained failure', R::release85Errors($rows, 'Source mismatch', $this->lexerLog()));
        [$r] = $this->assessment(); self::assertNotEmpty(E::claimErrors($r));
    }

    private function lexerLog(): string
    {
        return "Scanner rules: 190; direct cases: 2700; primitive cases: 101; syntax cases: 144; grammar bypass entries: 246; unexpected failures: 1\n"
            . "Unproven: exhaustive recursive state combinations and exact malformed-input recovery; see Phase 5 report.\n"
            . "Ledger stale: run composer lexer:coverage -- --write\n"
            . "Script php bin/lexer-coverage.php handling the lexer:coverage event returned with error code 1\n";
    }

    public function testSelectorTransitionCannotAuthorizeOtherScannerChanges(): void
    {
        $path = 'src/Php/Lexing/PhpVersion.php'; $old = ['input_hashes' => [$path => 'before'], 'rules' => ['unchanged']];
        $historical = S::json($old); $current = $old; $current['input_hashes'][$path] = 'after';
        $encode = static fn(array $data): string => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $review = ['historicalLedgerSha256' => hash('sha256', $historical), 'currentLedgerSha256' => hash('sha256', $encode($current)), 'inputChanges' => [$path => ['before' => 'before', 'after' => 'after']]];
        self::assertSame([], H::correspondenceErrors($review, $historical, $current, 'after'));
        $current['rules'][] = 'unauthorized'; $review['currentLedgerSha256'] = hash('sha256', $encode($current));
        self::assertContains('Scanner input, mapping, family or disposition changed outside approved selector hash', H::correspondenceErrors($review, $historical, $current, 'after'));
        self::assertContains('Unreviewed shared selector bytes', H::correspondenceErrors($review, $historical, $current, 'substituted'));
    }

    public function testReplacingSharedLexerAuthorityIsRejected(): void
    {
        $this->put(H::REVIEW, '{"inputChanges":{}}');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Untrusted shared lexer transition authority');
        (new H($this->temporary))->derive($this->temporary);
    }

    public function testLexerTransitionCannotHideCaseFailuresOrMissingCases(): void
    {
        self::assertSame([], H::executionErrors($this->lexerLog()));
        self::assertNotEmpty(H::executionErrors(str_replace('failures: 1', 'failures: 2', $this->lexerLog())));
        self::assertNotEmpty(H::executionErrors(str_replace('cases: 2700', 'cases: 2699', $this->lexerLog())));
        self::assertNotEmpty(H::executionErrors($this->lexerLog() . 'Unexpected mismatch'));
    }

    public function testReadOnlyGuardRejectsUnexpectedArtifactMutation(): void
    {
        $this->put('artifact.json', '{}'); $c = new C($this->temporary); $before = $c->hashes(['artifact.json']);
        self::assertSame([], E::readOnlyErrors($before, $before, []));
        $this->put('artifact.json', '{"changed":true}');
        self::assertSame(['Read-only validation modified artifacts'], E::readOnlyErrors($before, $c->hashes(['artifact.json']), $c->verifyHashes($before)));
    }
}
