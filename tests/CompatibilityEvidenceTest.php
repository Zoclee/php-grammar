<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PhpGrammar\Tools\CompatibilityEvidence as E;
use PhpGrammar\Tools\HistoricalEvidence as H;
use PhpGrammar\Tools\Support as S;
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../tools/lib/Support.php';
require_once __DIR__.'/../tools/lib/CompatibilityEvidence.php';
final class CompatibilityEvidenceTest extends TestCase
{
    private string $temp;
    private E $e;
    protected function setUp(): void {
        $this->temp=str_replace('\\','/',sys_get_temp_dir()).'/compatibility-evidence-'.bin2hex(random_bytes(6)); mkdir($this->temp); $this->e=new E($this->temp);
        $policy=json_decode(file_get_contents(__DIR__.'/../'.E::POLICY),true);
        foreach($policy['trees'] as $p=>$reason)mkdir($this->temp.'/'.$p,0777,true);
        foreach($policy['files'] as $p)$this->put($p,'fixture');
        $this->put(E::POLICY,S::json($policy));
        $this->put('tools/evidence/documentation-inputs.json',S::json(['files'=>['docs/8.6/shared-lexer-regression.json']]));
        $this->put('docs/8.6/shared-lexer-regression.json','{}');
        $this->put('docs/8.5/final-evidence.json','{}');
        $this->put('php-grammar.json',S::json(['versions'=>[]]));
        $this->put('schema/certification-compatibility.schema.json',file_get_contents(__DIR__.'/../schema/certification-compatibility.schema.json'));
        foreach(['grammar/8.5/php.ebnf','grammar/8.5/php.md','tests/fixtures/php/8.5/a.php','src/parser.php','src/lexer.php','tools/8.5/source-lock.json'] as $p)$this->put($p,'original');
        $this->put('tests/fixtures/version-boundaries/a.json',S::json(['name'=>'new-syntax','firstSupportedVersion'=>'8.6']));
    }
    protected function tearDown(): void {
        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temp,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST) as $f)$f->isDir()?rmdir($f->getPathname()):unlink($f->getPathname());
        rmdir($this->temp);
    }
    private function put(string $p,string $bytes): void { $file=$this->temp.'/'.$p; if(!is_dir(dirname($file)))mkdir(dirname($file),0777,true); file_put_contents($file,$bytes); }
    private function fixture(): array {
        $out=$this->temp.'/docs/8.5/compatibility/test';
        $config=E::defaults($this->temp); $commands=[];
        foreach($this->e->commands($config,$out) as $id=>$command) {
            $log="OK (2 tests, 4 assertions)\n"; $this->put('docs/8.5/compatibility/test/'.$id.'.log',$log);
            $commands[$id]=['command'=>$command,'cwd'=>$this->temp,'exit_code'=>0,'log'=>$id.'.log','logSha256'=>hash('sha256',$log),'totals'=>E::totals($log)];
            $this->put('docs/8.5/compatibility/test/'.$id.'.json',S::json($commands[$id]));
        }
        $boundary=S::json(['errors'=>[],'observations'=>[...array_fill(0,70,['test'=>'isolated-validator-fixture']),
            ['id'=>'new-syntax','version'=>'8.5','stage'=>'structural','result'=>['matched'=>false]],
            ['id'=>'new-syntax','version'=>'8.6','stage'=>'structural','result'=>['matched'=>true]]]]);
        $this->put('docs/8.5/compatibility/test/boundaries.json',$boundary);
        $r = ['schemaVersion'=>E::SCHEMA,'evidenceKind'=>'current-compatibility','contract'=>E::CONTRACT,'version'=>'8.5','claim'=>E::CLAIM,
            'certificationGranted'=>false,'generatedAt'=>'2026-10-25T00:00:00Z','repository'=>['commit'=>str_repeat('a',40),'workingTree'=>'fixture'],
            'historicalReference'=>['commit'=>H::COMMIT,'ledgerSha256'=>H::CHECKOUT_SHA256,'integrityImplied'=>false],
            'configuration'=>$config,'environment'=>['errors'=>[]],'dependencies'=>$this->e->inventory(),'inputsUnchanged'=>true,
            'commands'=>$commands,'boundarySha256'=>hash('sha256',$boundary),'missingPrerequisites'=>[],'limitations'=>E::limitations()];
        $this->put('docs/8.5/compatibility/test/evidence.json',S::json($r));
        $this->put('docs/8.5/compatibility/test/run-receipt.json',S::json(['reportSha256'=>hash('sha256',S::json($r))]));
        return $r;
    }
    private function errors(array $r): array { return $this->e->verify($r,$this->temp.'/docs/8.5/compatibility/test',false); }
    public function testMaterialMutationsAndScopeAdditionsRemovalsInvalidate(): void {
        $r=$this->fixture(); self::assertSame([],$this->errors($r));
        foreach(['grammar/8.5/php.ebnf','grammar/8.5/php.md','tests/fixtures/php/8.5/a.php','src/parser.php','src/lexer.php','tools/8.5/source-lock.json','tests/fixtures/version-boundaries/a.json','docs/8.6/shared-lexer-regression.json'] as $p) {
            $before=file_get_contents($this->temp.'/'.$p); $this->put($p,'mutation');
            self::assertContains('Stale or incomplete mandatory dependency scope',$this->errors($r),$p);
            $this->put($p,$before);
        }
        $this->put('src/new-validator.php','new'); self::assertNotEmpty($this->errors($r)); unlink($this->temp.'/src/new-validator.php');
        unlink($this->temp.'/src/parser.php'); self::assertNotEmpty($this->errors($r));
    }
    public function testUnreferencedResearchDoesNotInvalidateButLiteralDependencyDoes(): void {
        $r=$this->fixture(); $this->put('docs/8.6/unrelated-research.md','research'); self::assertSame([],$this->errors($r));
        $this->put('src/parser.php',"<?php file_get_contents('docs/8.6/unrelated-research.md');");
        $r['dependencies']=$this->e->inventory();
        self::assertArrayHasKey('docs/8.6/unrelated-research.md',$r['dependencies']);
        $this->put('docs/8.6/unrelated-research.md','changed'); self::assertNotEmpty($this->errors($r));
    }
    public function testOmissionSchemaClaimAndExecutionTamperingFail(): void {
        $r=$this->fixture();
        $bad=$r; unset($bad['dependencies']['src/parser.php']); self::assertNotEmpty($this->errors($bad));
        foreach(['schemaVersion'=>'legacy','evidenceKind'=>'historical-certification','contract'=>'unknown','certificationGranted'=>true,'claim'=>'certified','version'=>'8.6'] as $field=>$value) {
            $bad=$r; $bad[$field]=$value; self::assertNotEmpty($this->errors($bad),$field);
        }
        $bad=$r; $bad['historicalReference']['integrityImplied']=true; self::assertNotEmpty($this->errors($bad));
        $bad=$r; unset($bad['commands']['paired-boundaries']); self::assertNotEmpty($this->errors($bad));
        $bad=$r; $bad['commands']['full-regression']['exit_code']=1; self::assertNotEmpty($this->errors($bad));
        $bad=$r; $bad['commands']['full-regression']['totals']['tests']=999; self::assertNotEmpty($this->errors($bad));
        $bad=$r; $bad['inputsUnchanged']=false; self::assertNotEmpty($this->errors($bad));
        $bad=$r; $bad['missingPrerequisites']=['source']; self::assertNotEmpty($this->errors($bad));
        $this->put('docs/8.5/compatibility/test/full-regression.log','tamper'); self::assertNotEmpty($this->errors($r));
    }
    public function testBoundaryEvidenceRequiredAndVerificationDoesNotWrite(): void {
        $r=$this->fixture(); $before=[];
        foreach(S::files($this->temp) as $p)$before[$p]=hash_file('sha256',$p);
        self::assertSame([],$this->errors($r));
        foreach($before as $p=>$h)self::assertSame($h,hash_file('sha256',$p));
        unlink($this->temp.'/docs/8.5/compatibility/test/boundaries.json');
        self::assertContains('Missing/tampered version-boundary evidence',$this->errors($r));
    }
    public function testOutputsCannotHideExecutableDependencies(): void {
        $r=$this->fixture(); $this->put('docs/8.5/compatibility/test/injected.php','<?php');
        self::assertContains('Unexpected or missing output artifact',$this->errors($r));
    }
    public function testValidLookingTimestampAndRepositoryTamperingAreRejected(): void {
        $r=$this->fixture();
        $r['generatedAt']='2026-10-24T00:00:00Z';
        self::assertContains('Tampered evidence/run metadata',$this->errors($r));
        $r=$this->fixture(); $r['repository']['commit']=str_repeat('b',40);
        self::assertContains('Tampered evidence/run metadata',$this->errors($r));
        $r=$this->fixture(); $this->put('docs/8.5/compatibility/test/evidence.json',S::json($r).' ');
        self::assertContains('Tampered evidence/run metadata',$this->errors($r));
    }
    public function testRehashedBoundaryEvidenceStillNeedsEveryVersionAndOutcome(): void {
        $r=$this->fixture(); $path='docs/8.5/compatibility/test/boundaries.json';
        $b=json_decode(file_get_contents($this->temp.'/'.$path),true);
        $b['observations'][71]['result']['matched']=false;
        $bytes=S::json($b); $this->put($path,$bytes); $r['boundarySha256']=hash('sha256',$bytes);
        self::assertContains('Incorrect boundary outcome: new-syntax/8.6',$this->errors($r));
        $b['observations'][71]=['test'=>'invented']; $bytes=S::json($b); $this->put($path,$bytes); $r['boundarySha256']=hash('sha256',$bytes);
        self::assertContains('Missing/duplicate boundary result: new-syntax/8.6',$this->errors($r));
    }
    public function testHybridCannotCertifyWithMissingEvidenceAndUnresolvedBlockers(): void {
        $root=dirname(__DIR__);
        $r=S::run([PHP_BINARY,'-n','tools/8.6/certification.php','--contract','historical-plus-current-v1','--claims',
            '--historical-snapshot',$this->temp,'--historical-supplement',$this->temp,'--compatibility-report','missing.json'], $root);
        self::assertSame(1,$r['exit_code']);
        self::assertStringContainsString('"certificationGranted": false',$r['output']);
        foreach(['PHP86-013','PHP86-014','PHP86-015'] as $id)self::assertStringContainsString($id,$r['output']);
    }
}
