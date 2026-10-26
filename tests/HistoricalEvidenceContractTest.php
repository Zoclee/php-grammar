<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PhpGrammar\Tools\HistoricalEvidence as H;
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../tools/lib/Support.php';
require_once __DIR__ . '/../tools/lib/HistoricalEvidence.php';
final class HistoricalEvidenceContractTest extends TestCase
{
    private string $temp;
    protected function setUp(): void { $this->temp=sys_get_temp_dir().'/historical-evidence-'.bin2hex(random_bytes(6)); mkdir($this->temp); }
    protected function tearDown(): void {
        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temp,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST) as $f) $f->isDir()?rmdir($f->getPathname()):unlink($f->getPathname());
        rmdir($this->temp);
    }
    private function put(string $p,string $bytes): void { $p=$this->temp.'/'.$p; if(!is_dir(dirname($p)))mkdir(dirname($p),0777,true); file_put_contents($p,$bytes); }
    public function testRawBytesRequireExactTrustedDigestAndDoNotNormalize(): void {
        $path=H::SUPPLEMENT[0]; $bytes="a\r\nb\n";
        $this->put('snapshot/'.$path,"a\nb\n");
        $expected=[$path=>hash('sha256',$bytes)];
        $before=file_get_contents($this->temp.'/snapshot/'.$path);
        self::assertCount(1,H::inspectInputs($expected,$this->temp.'/snapshot',$this->temp.'/missing')['errors']);
        $this->put('supplement/'.$path,$bytes);
        $r=H::inspectInputs($expected,$this->temp.'/snapshot',$this->temp.'/supplement');
        self::assertSame([],$r['errors']); self::assertSame('preserved-supplement',$r['inputs'][$path]['source']);
        self::assertSame($before,file_get_contents($this->temp.'/snapshot/'.$path));
        $this->put('supplement/'.$path,"a\nb\n");
        self::assertCount(1,H::inspectInputs($expected,$this->temp.'/snapshot',$this->temp.'/supplement')['errors']);
    }
    public function testNoArbitrarySupplementOrTraversalFallback(): void {
        $this->put('supplement/grammar/8.5/php.ebnf','changed');
        $r=H::inspectInputs(['grammar/8.5/php.ebnf'=>hash('sha256','changed')],$this->temp.'/missing',$this->temp.'/supplement');
        self::assertNotEmpty($r['errors']);
        self::assertNull(H::safeFile($this->temp,'../outside'));
        self::assertNull(H::safeFile($this->temp,'C:/outside'));
    }
    public function testAllEightPathsHaveIndependentMissingAndTamperOutcomes(): void {
        $expected=[];
        foreach(H::SUPPLEMENT as $i=>$path) { $bytes='original-'.$i."\r\n"; $this->put('supplement/'.$path,$bytes); $expected[$path]=hash('sha256',$bytes); }
        self::assertSame([],H::inspectInputs($expected,$this->temp.'/missing',$this->temp.'/supplement')['errors']);
        foreach(H::SUPPLEMENT as $path) {
            $file=$this->temp.'/supplement/'.$path; $bytes=file_get_contents($file); unlink($file);
            self::assertCount(1,H::inspectInputs($expected,$this->temp.'/missing',$this->temp.'/supplement')['errors']);
            file_put_contents($file,'tampered');
            self::assertFalse(H::inspectInputs($expected,$this->temp.'/missing',$this->temp.'/supplement')['inputs'][$path]['verified']);
            file_put_contents($file,$bytes);
        }
    }
    public function testTamperedLedgerCannotBeAuthenticatedByItsOwnHash(): void {
        $this->put(H::LEDGER,'{}');
        $r=H::verify($this->temp,$this->temp,$this->temp);
        self::assertFalse($r['complete']);
        self::assertContains('Historical ledger tampered or missing',$r['errors']);
        self::assertFalse($r['currentCertification']); self::assertFalse($r['historicalTestsRerun']);
    }
}
