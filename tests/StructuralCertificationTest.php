<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
require_once __DIR__.'/../tools/lib/bootstrap.php';
require_once __DIR__.'/../tools/lib/StructuralCertification.php';
use PhpGrammar\Tools\{StructuralCertification as C,Support as S};
use PHPUnit\Framework\TestCase;

final class StructuralCertificationTest extends TestCase
{
    private function c(array $overrides=[]):C{return new C(str_replace('\\','/',dirname(__DIR__)),$overrides);}
    private function changed(string $p,callable $edit):C{$r=json_decode(file_get_contents(dirname(__DIR__).'/'.$p),true,512,JSON_THROW_ON_ERROR);return$this->c([$p=>S::json($edit($r))]);}
    public function testIndependentContractAndCurrentCanonicalCorrection():void {
        $c=$this->c();self::assertCount(27,C::gates());self::assertSame([], $c->grammarErrors());self::assertSame([], $c->parityErrors());self::assertSame([], $c->alternativeErrors());self::assertSame([], $c->scopeErrors());self::assertSame([], $c->metadataErrors());self::assertSame([], $c->boundaryErrors());
        self::assertNotSame(\PhpGrammar\Tools\GaCertification::CONTRACT,C::CONTRACT);
    }
    public function testInvalidEbnfAndBrokenReferencesCannotCertify():void {
        $p='grammar/8.6/php.ebnf';$text=file_get_contents(dirname(__DIR__).'/'.$p);
        self::assertNotEmpty($this->c([$p=>'source-file = [ ;'])->grammarErrors());
        self::assertStringContainsString('undefined-production',implode(';',$this->c([$p=>str_replace('    top-statement-list ;','    invented-reference ;',$text)])->grammarErrors()));
    }
    public function testWrongAlternativesCannotBeBlessedByUpdatingADigest():void {
        $p='grammar/8.6/php.ebnf';$text=file_get_contents(dirname(__DIR__).'/'.$p);
        self::assertStringContainsString('Unreviewed structural alternative: argument',implode(';',$this->c([$p=>str_replace('argument =','argument = "invented" |',$text)])->alternativeErrors()));
        self::assertNotEmpty($this->c([$p=>str_replace('    identifier ;', '    "get" | "set" ;',$text)])->alternativeErrors());
    }
    public function testParityAndMissingDocumentedChangesFail():void {
        $p='grammar/8.6/php.md';self::assertNotEmpty($this->c([$p=>'No production block'])->parityErrors());
        $p='grammar/8.6/changes.md';$text=str_replace(['PHP86-001','## 2. Added syntax'],['omitted','## Removed required section'],file_get_contents(dirname(__DIR__).'/'.$p));
        self::assertNotEmpty($this->c([$p=>$text])->gate('change-traceability')['errors']);
    }
    public function testWrongBoundaryExpectationFailsAgainstIndependentBaseline():void {
        $p='tests/fixtures/version-boundaries/8.6/pfa-basic.json';
        self::assertNotEmpty($this->changed($p,static function($r){$r['firstSupportedVersion']='8.5';return$r;})->boundaryErrors());
    }
    public function testMissingSourceMappingsAndStaleLocksFail():void {
        $c=$this->changed('tools/8.6/change-map-structural-v1.json',static function($r){$r['findings']['PHP86-001']['sources']=[];return$r;});
        self::assertContains('Missing mapping: PHP86-001',$c->gate('source-references')['errors']);
        $p='tools/8.6/source-lock-ga-v2.json';self::assertContains('Stale/unreviewed GA source lock',$this->c([$p=>file_get_contents(dirname(__DIR__).'/'.$p)."\n"])->sourceErrors());
    }
    public function testTamperedEvidenceAndUnsupportedClaimsFail():void {
        $r=['contract'=>C::CONTRACT,'result'=>'passed'];$bytes=S::json($r);$hash=hash('sha256',$bytes);
        self::assertSame([],C::receiptErrors($r,$bytes,$hash));$r['result']='failed';self::assertNotEmpty(C::receiptErrors($r,$bytes,$hash));self::assertNotEmpty(C::receiptErrors($r,$bytes.'x',$hash));
        self::assertSame(['Unsupported structural evidence identity/claim'],$this->c()->verify(['contract'=>C::CONTRACT,'schema'=>C::SCHEMA,'claim'=>'Fully equivalent to PHP'], 'missing'));
    }
    public function testUnresolvedStructuralDefectCannotBeHiddenByScope():void {
        $c=$this->changed(C::OUT.'/scope.json',static function($r){$r['knownStructuralDefects'][0]['status']='unresolved';return$r;});
        self::assertContains('Unresolved or omitted material structural defect',$c->scopeErrors());
        $c=$this->changed(C::OUT.'/scope.json',static function($r){$r['blockers'][0]['extendedStatus']='resolved';return$r;});self::assertContains('Unsupported scope disposition',$c->scopeErrors());
    }
    public function testIncorrectMetadataAndMissingTestsFail():void {
        $c=$this->changed('php-grammar.json',static function($r){$r['versions'][1]['phpSource']['commit']=str_repeat('0',40);return$r;});self::assertContains('Incorrect structural package metadata',$c->metadataErrors());
        $p='tests/Php/Conformance/Php86StructuralHooksTest.php';self::assertContains('Missing required test: '.$p,$this->c([$p=>''])->metadataErrors());
    }
    public function testBrokenHistoricalCompatibilityCannotBeWaived():void {
        $p='grammar/8.5/php.ebnf';self::assertContains('Historical input changed: '.$p,$this->c([$p=>'source-file = "anything" ;'])->historicalErrors());
        $c=$this->changed('php-grammar.json',static function($r){$r['versions'][0]['status']='draft';return$r;});self::assertContains('PHP 8.5 manifest changed',$c->metadataErrors());
    }
    public function testMissingGatesAreRejectedBeforeReceipts():void {
        $r=['contract'=>C::CONTRACT,'schema'=>C::SCHEMA,'claim'=>C::CLAIM,'sourceCommit'=>\PhpGrammar\Tools\GaCertification::PIN,'output'=>C::OUT.'/runs/test','results'=>[]];
        self::assertSame(['Missing/reordered structural gate'],$this->c()->verify($r,$r['output'].'/evidence.json'));
    }
    public function testLostGapClassificationAndIncorrectPackageMetadataFail():void {
        $c=$this->changed('docs/8.6/scanner-grammar-binding-closure/coverage.json',static function($r){$r['rows']=array_values(array_filter($r['rows'],static fn($row)=>$row['id']!=='comment'));return$r;});
        self::assertContains('Unclassified meaningful structural gap: comment',$c->coverage()['errors']);
        $c=$this->changed('composer.json',static function($r){$r['name']='incorrect/package';return$r;});self::assertContains('Incorrect Composer package metadata',$c->gate('package-integrity')['errors']);
    }
}
