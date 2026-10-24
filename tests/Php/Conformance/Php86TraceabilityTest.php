<?php
declare(strict_types=1);
namespace PhpGrammar\Tests\Php\Conformance;
require_once dirname(__DIR__,3).'/tools/lib/ChangeTraceability.php';
use PhpGrammar\Tools\ChangeTraceability;
use PHPUnit\Framework\TestCase;

final class Php86TraceabilityTest extends TestCase
{
    private function validate(array $edit=[],array $override=[]):array {
        $root=dirname(__DIR__,3);
        $map=json_decode(file_get_contents($root.'/tools/8.6/change-map.json'),true);
        return ChangeTraceability::validate($root,array_replace_recursive($map,$edit),$override);
    }
    public function testReviewedDifferences():void {
        $r=$this->validate();self::assertSame([],$r['errors']);
        self::assertSame([],$r['differences']['added']);self::assertSame([],$r['differences']['removed']);
        self::assertCount(5,$r['differences']['modified']);self::assertCount(13,$r['documentationSections']);
    }
    public function testUnreviewedAlternativeFails():void {
        $p='grammar/8.6/php.ebnf';$s=file_get_contents(dirname(__DIR__,3).'/'.$p);
        $s=str_replace("argument =", "argument =\n    \"invented\" |",$s);
        $r=$this->validate([],[$p=>$s]);self::assertContains('Unreviewed production structure: argument',$r['errors']);
    }
    public function testAddedAndRemovedProductionInventory():void {
        $p='grammar/8.6/php.ebnf';$s=file_get_contents(dirname(__DIR__,3).'/'.$p);
        $s=preg_replace('/\bsource-file\s*=/', 'new-root =',$s,1);
        $r=$this->validate([],[$p=>$s]);self::assertContains('new-root',$r['differences']['added']);self::assertContains('source-file',$r['differences']['removed']);
        self::assertContains('Unmapped production difference: new-root',$r['errors']);
    }
    public function testMissingOrStaleEvidenceFails():void {
        $r=$this->validate(['findings'=>['PHP86-001'=>['productions'=>['nonexistent'],'fixtures'=>['tests/missing.php'=>'bad']]]]);
        self::assertContains('Invalid production reference: nonexistent',$r['errors']);
        self::assertContains('Missing/stale test reference: tests/missing.php',$r['errors']);
        $r=$this->validate(['findings'=>['PHP86-001'=>['fixtures'=>['tests/fixtures/php/8.6/valid/pfa-basic.php'=>'bad']]]]);
        self::assertContains('Missing/stale test reference: tests/fixtures/php/8.6/valid/pfa-basic.php',$r['errors']);
    }
    public function testDocumentaryChangesAndMissingIdentifiersFail():void {
        $p='grammar/8.6/php.md';$s=file_get_contents(dirname(__DIR__,3).'/'.$p);$r=$this->validate([],[$p=>$s."\n## Unreviewed rule\nInvented.\n"]);
        self::assertContains('Unreviewed documentation difference: ## Unreviewed rule',$r['errors']);
        $p='grammar/8.6/changes.md';$s=file_get_contents(dirname(__DIR__,3).'/'.$p);$s=str_replace(['PHP86-010','## 2. Added syntax'],['missing','## Missing'],$s);$r=$this->validate([],[$p=>$s]);
        self::assertContains('Missing change identifier: PHP86-010',$r['errors']);
        self::assertContains('Required ten change sections missing or reordered',$r['errors']);
    }
    public function testUnreviewedLexicalConfigurationFails():void {
        $p='src/Php/Lexing/PhpVersion.php';
        $r=$this->validate([],[$p=>file_get_contents(dirname(__DIR__,3).'/'.$p)."\n// unreviewed lexical change\n"]);
        self::assertContains('Unreviewed lexical/adapter input: '.$p,$r['errors']);
    }
}
