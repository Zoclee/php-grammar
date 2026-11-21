<?php
declare(strict_types=1);
namespace PhpGrammar\Tests\Php\Conformance;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};

final class Php86StructuralHooksTest extends TestCase
{
    public static function cases():iterable {
        $root=dirname(__DIR__,3);$data=json_decode(file_get_contents($root.'/tests/fixtures/php/8.6/structural-hooks/cases.json'),true,512,JSON_THROW_ON_ERROR);
        foreach($data['cases']as$c)yield$c['id']=>[$c];
    }
    #[DataProvider('cases')]
    public function testCurrentStructuralAndHistoricalBoundary(array $c):void {
        $root=dirname(__DIR__,3);$text=file_get_contents($root.'/'.$c['fixture']);self::assertSame($c['sha256'],hash('sha256',$text));
        $matcher=PhpGrammarMatcher::forRepositoryRoot($root);self::assertSame($c['structural'],$matcher->matches('8.6',$text)->matched);
        $legal=in_array(strtolower(hex2bin($c['nameHex'])),['get','set'],true);
        self::assertSame($legal,$matcher->matches('8.5',$text)->matched,'Historical representational restriction, not a PHP 8.6 language addition');
        $observations=json_decode(file_get_contents($root.'/docs/8.6/structural-certification/hooks-after.json'),true,512,JSON_THROW_ON_ERROR);
        $row=array_column($observations['observations'],null,'id')[$c['id']];self::assertSame($c['lintAccepted'],$row['lint']['exit_code']===0);
        if($c['unknownHook'])self::assertStringContainsString('Unknown hook',base64_decode($row['lint']['outputBase64'],true));
    }
    public function testHistoricalReconciliationHookCasesNowMatchOnly86():void {
        $root=dirname(__DIR__,3);$m=PhpGrammarMatcher::forRepositoryRoot($root);
        foreach(['hook-live','hook-retained','hook-discarded','hook-constant-discarded']as$id){$s=file_get_contents($root.'/tests/fixtures/php/8.6/reconciliation/'.$id.'.php');self::assertTrue($m->matches('8.6',$s)->matched);self::assertFalse($m->matches('8.5',$s)->matched);}
    }
}
