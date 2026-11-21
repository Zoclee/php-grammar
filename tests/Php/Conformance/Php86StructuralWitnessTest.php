<?php
declare(strict_types=1);
namespace PhpGrammar\Tests\Php\Conformance;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};

final class Php86StructuralWitnessTest extends TestCase
{
    public static function cases():iterable {
        $root=dirname(__DIR__,3);$r=json_decode(file_get_contents($root.'/tests/fixtures/php/8.6/structural-evidence/cases.json'),true,512,JSON_THROW_ON_ERROR);foreach($r['cases']as$c)yield$c['id']=>[$c];
    }
    #[DataProvider('cases')]
    public function testSourceBackedStructuralWitness(array $c):void {
        $root=dirname(__DIR__,3);$m=PhpGrammarMatcher::forRepositoryRoot($root);$s=file_get_contents($root.'/'.$c['fixture']);
        self::assertSame($c['sha256'],hash('sha256',$s));self::assertSame($c['structuralExpected'],$m->matches('8.6',$s)->matched);self::assertSame($c['ruleExpected'],$m->matchesRule('8.6',$c['rule'],$c['fragment'])->matched);
        $r=json_decode(file_get_contents($root.'/docs/8.6/structural-certification/witnesses-v4.json'),true,512,JSON_THROW_ON_ERROR);$row=array_column($r['observations'],null,'id')[$c['id']];self::assertSame($c['lintExit'],$row['lint']['exit_code']);
    }
    public function testYieldCorrectionPreservesHistoricalDifferenceAndNegativeBoundaries():void {
        $root=dirname(__DIR__,3);$m=PhpGrammarMatcher::forRepositoryRoot($root);
        foreach(['arrow','include','print']as$id){$s=file_get_contents($root.'/tests/fixtures/php/8.6/structural-evidence/prefix-'.$id.'.php');self::assertFalse($m->matches('8.5',$s)->matched);self::assertTrue($m->matches('8.6',$s)->matched);}
        foreach(['and','or','xor']as$operator)self::assertFalse($m->matches('8.6','<?php function g(){yield $a '.$operator.' throw new Exception => 2;}')->matched);
    }
}
