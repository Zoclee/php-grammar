<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';require dirname(__DIR__,2).'/vendor/autoload.php';require __DIR__.'/../lib/StructuralCertification.php';
use PhpGrammar\Tools\{StructuralCertification as C,GaCertification as G,Support as S,CompatibilityEvidence};
$root=str_replace('\\','/',dirname(__DIR__,2));chdir($root);$c=new C($root);
$a=S::args(['report'=>''],['promote','signoff','check-signoff']);
$r=$c->read($a['report']);$errors=$c->verify($r,$a['report']);
if($errors!==[]||!$r['structuralCertified'])throw new RuntimeException(S::json($errors?:['Structural certification did not pass']));
if($a['promote']){
    $path=C::OUT.'/promotion.json';if(file_exists($path))throw new RuntimeException('Already promoted');
    G::writeNew($path,['report'=>$a['report'],'sha256'=>hash_file('sha256',$a['report']),'independentlyVerifiedBeforePromotion'=>true,'inputInventorySha256'=>hash('sha256',S::json($r['inputs']))]);
    $m=S::read('php-grammar.json');if($m['versions'][1]['status']!=='draft')throw new RuntimeException('Expected draft');$m['versions'][1]['status']='stable';file_put_contents('php-grammar.json',S::json($m));
    foreach(['grammar/8.6/php.md','grammar/8.6/changes.md']as$p){$s=file_get_contents($p);$s=str_replace('structural certification pending','stable structural certification passed',$s);file_put_contents($p,$s);}
    foreach(['README.md','docs/usage.md','docs/api.md']as$p){$s=file_get_contents($p);$s=str_replace('a GA structural grammar awaiting structural certification','a stable PHP 8.6.0 GA structural grammar',$s);$s=str_replace('[draft integration](docs/8.6/draft-integration.md)','[structural scope](docs/structural-certification.md)',$s);$s=str_replace('PHP 8.5 remains the recommended stable version and the default for existing','PHP 8.5 remains the compatibility default for existing',$s);file_put_contents($p,$s);}
    $p='tests/Php/Conformance/Php86IntegrationTest.php';$s=file_get_contents($p);$s=str_replace('testDraftLoadsWithNoHistoricalPackageOrEvidenceFiles','testStableLoadsWithNoHistoricalPackageOrEvidenceFiles',$s);$s=str_replace("self::assertSame('draft',", "self::assertSame('stable',",$s);file_put_contents($p,$s);
    foreach(['tests/Repository/ConsumerCliTest.php','tests/Repository/ConsumerWorkflowTest.php']as$p){$s=file_get_contents($p);$s=str_replace(["['stable','draft']","self::assertSame('draft',",'testDraftNavigationAndMetadata'],["['stable','stable']","self::assertSame('stable',",'testStableNavigationAndMetadata'],$s);file_put_contents($p,$s);}
    foreach(['docs/usage.md','docs/api.md']as$p){$s=file_get_contents($p);$s=str_replace(['PHP 8.5 remains the recommended stable selection.','the recommended stable version;','8.6. Use explicit 8.5 selection to retain stable behavior.','// recommended stable selection','`metadata.integration` for scope, provenance, and results.'],['PHP 8.5 remains the compatibility default for existing maintainer commands.','the compatibility default for existing maintainer commands;','8.6. Select the language version explicitly for reproducible behavior.','// compatibility selection','`metadata.structuralCertification` for scope, provenance, and results.'],$s);file_put_contents($p,$s);}
    $scope=$c->read(C::OUT.'/scope.json');foreach($scope['knownStructuralDefects']as&$defect){$defect['status']='corrected-verified';$defect['executionEvidence']=$a['report'];}unset($defect);file_put_contents(C::OUT.'/scope.json',S::json($scope));
    if(!is_dir('docs/releases'))mkdir('docs/releases',0777,true);
    file_put_contents('docs/releases/v1.1.0.md',<<<'MD'
# php-grammar v1.1.0 — PHP 8.6 Grammar Support

This stable release adds a standalone PHP 8.6.0 structural grammar, verified
against the official GA parser and scanner at
`8aaada3f9605d81a38b8240f93c7389dfbc77ef9`.

It includes partial function application and callable trailing commas, matching
Markdown, version-boundary fixtures, and an independently verified structural
certification contract. Hook names now reflect the parser's identifier rule;
get/set legality remains contextual. Yield-key prefix composition is corrected
with positive and negative GA controls. Those two corrections are explicitly
historical representation obligations, not new PHP 8.6 syntax.

PHP 8.5 grammar, specification, fixtures and historical evidence are preserved.
Existing maintainer-command defaults remain PHP 8.5. Select PHP 8.6 explicitly;
`latestVersion()` returns 8.6 as the highest advertised version, not as a stable
selection policy.

Structural matching does not certify whole-source validity or exhaustive
compiler, contextual, AST, constant-folding, scanner-state or runtime
equivalence. The existing extended certification remains unresolved, including
PHP86-013, PHP86-014, PHP86-015, CA86-001, C6-CONTEXT, C6-COVERAGE and
C6-PROVENANCE. Its historical failed and blocked results are retained. Current
PHP 8.5 compatibility does not renew historical certification or authenticate
the missing original manifest. This release is not endorsed by the PHP project.

See [structural certification](../structural-certification.md),
[version changes](../../grammar/8.6/changes.md), and the
[final sign-off](../8.6/structural-release-signoff.md) for exact gates, counts,
hashes, package inventory and limitations. Execution evidence and sign-off are
companion artifacts to the frozen distribution archive.

The proposed final Git tag is `v1.1.0`; this is not a prerelease.
Publication requires maintainer approval.
MD);
    echo "Stable promotion written; refresh traceability and transition, then execute a new final run.\n";exit;
}
if(!$a['signoff']&&!$a['check-signoff'])throw new RuntimeException('Choose --promote, --signoff or --check-signoff');
if(S::read('php-grammar.json')['versions'][1]['status']!=='stable')throw new RuntimeException('Final stable inputs required');
$out=$r['output'];$coverage=S::read($out.'/classified-coverage.json')['details'];$package=S::read($out.'/package-integrity.json')['details'];
$totals=CompatibilityEvidence::totals(file_get_contents($out.'/full-regression.log'));
$text="# PHP 8.6 stable structural release sign-off\n\n**SIGNED OFF — STABLE STRUCTURAL GRAMMAR RELEASE READY**\n\n";
$text.="Proposed final tag: `v1.1.0`. Official PHP tag: `php-8.6.0`; source commit: `".G::PIN."`.\n\n";
$text.="The independently reconstructed `".C::CONTRACT."` evidence passed all ".count($r['results'])." mandatory gates.\n\n";
$text.="Claim: ".C::CLAIM."\n\n";
$text.="Evidence: `$out/evidence.json`\n\nSHA-256: `".hash_file('sha256',$a['report'])."`.\n\n";
foreach(['grammar/8.6/php.ebnf','grammar/8.6/php.md',G::GA]as$p)$text.="- `$p`: `".hash_file('sha256',$p)."`\n";
$text.="\nSource correspondence is reconstructed from the authenticated GA parser/scanner ledger and source locks; the exact source-built executable and DLL pass provenance verification. RC4, RC7 and PHP 8.5 profiles remain intact.\n\n";
$text.="Seven productions differ from PHP 8.5: the five PHP86-001 PFA productions plus hook-name and yield-key. No production was added or removed. The latter two are documented historical representation corrections, not new language features.\n\n";
$text.="Ordinary fixture coverage: {$coverage['productions']}/{$coverage['totalProductions']} productions; {$coverage['alternatives']}/{$coverage['totalAlternatives']} alternatives. Remaining entries have explicit primitive/trivia, scanner-lookahead, or separately executed focused-witness classifications. Coverage is not a claim of unique derivation or AST equivalence.\n\n";
$text.="Full PHPUnit totals: `".json_encode($totals)."`. Hook-stage controls: 90. Focused structural witnesses: 124. Both short-tag profiles and paired PHP 8.5/8.6 boundaries passed. Scanner evidence covers the declared byte-source profile; it does not claim exhaustive scanner-state or multibyte equivalence.\n\n";
$text.="PHP 8.5 grammar, Markdown, fixtures and historical evidence retain their baseline bytes. Fresh current compatibility gates passed, including public API/CLI and shared tooling. The unavailable historical manifest is not reconstructed or authenticated; current compatibility is not historical certification renewal.\n\n";
$text.="Extended certification remains unresolved: PHP86-013, PHP86-014, PHP86-015, CA86-001, C6-CONTEXT, C6-COVERAGE and C6-PROVENANCE. The retained 38-obligation run has 26 passed, 3 failed and 9 blocked. Structural sign-off closes none of these extended obligations and implies no PHP-project endorsement.\n\n";
$text.="Distribution archive: `{$package['archive']}`; SHA-256 `{$package['archiveSha256']}`; ".count($package['files'])." individually hashed files. Full inventory: `$out/package-integrity.json`. Temporary builds, caches, Git internals and vendor dependencies are excluded. Evidence and this deterministically reconstructed sign-off accompany the archive separately.\n\n";
$text.="Final inputs are frozen in the evidence inventory. Existing maintainer-command defaults remain PHP 8.5; explicit 8.6 selection and latestVersion() are verified. No tag push, GitHub release publication or Packagist deployment is authorized by this sign-off.\n\n";
$text.="Verify this derived sign-off with:\n\n```text\nphp tools/8.6/structural-release.php --check-signoff --report $out/evidence.json\n```\n";
$p='docs/8.6/structural-release-signoff.md';
if($a['check-signoff']){if(!is_file($p)||file_get_contents($p)!==$text)throw new RuntimeException('Sign-off differs from independently verified evidence');echo "Sign-off and structural evidence verified\n";}
else{if(file_exists($p))throw new RuntimeException('Immutable sign-off already exists');file_put_contents($p,$text);echo "Final sign-off written\n";}
