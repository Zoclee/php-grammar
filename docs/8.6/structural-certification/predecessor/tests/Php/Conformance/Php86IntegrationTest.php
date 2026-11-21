<?php
declare(strict_types=1);
namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\GrammarRepository;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Php\Lexing\PhpVersion;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\TestCase;

final class Php86IntegrationTest extends TestCase
{
    private static function root(): string {return dirname(__DIR__,3);}

    public function testIndependentVersionConfigurationAndStructuralBoundary(): void
    {
        $matcher=PhpGrammarMatcher::forRepositoryRoot(self::root());
        foreach(['8.5','8.6']as $v){
            $config=PhpVersion::forVersion($v);
            self::assertSame($v,$config->version);
            self::assertSame($v,$config->withShortOpenTag(false)->version);
            self::assertFalse($config->isKeyword('let'));self::assertFalse($config->isKeyword('is'));
            self::assertTrue($config->isKeyword('readonly'));
            self::assertSame($v==='8.6',$matcher->matches($v,'<?php f(?);')->matched);
            self::assertTrue($matcher->matches($v,'<?php function let() {}')->matched);
            self::assertFalse($matcher->matches($v,'<?php use function A as readonly;')->matched);
            $tokens=Lexer::forVersion($v)->tokenize('<?php f(?, ..., ...$args, a: ?);')->withoutTrivia()->all();
            self::assertSame(['<?php','f','(','?',',','...',',','...','$args',',','a',':','?',')',';'],array_map(static fn($t)=>trim($t->lexeme),$tokens));
            foreach(['<?php /* unterminated','<?php "unterminated']as $bad)self::assertFalse($matcher->matches($v,$bad)->matched);
        }
    }

    public function testBothProfilesAndCompatibilityCorpus(): void
    {
        $data=json_decode(file_get_contents(self::root().'/tests/fixtures/php/8.6/integration-cases.json'),true);
        $matcher=PhpGrammarMatcher::forRepositoryRoot(self::root());
        foreach(['8.5','8.6']as $v)foreach([true,false]as $short){
            foreach($data['cases']as $case)self::assertSame($case['accepted'],$matcher->withShortOpenTag($short)->matches($v,file_get_contents(self::root().'/'.$case['fixture']))->matched,$v.' '.$case['id']);
            self::assertSame(!$short,$matcher->withShortOpenTag($short)->matches($v,'<? echo ;')->matched);
            self::assertTrue($matcher->withShortOpenTag($short)->matches($v,'<?= 1 ?>')->matched);
        }
    }

    public function testDraftLoadsWithNoHistoricalPackageOrEvidenceFiles(): void
    {
        $root=sys_get_temp_dir().'/php86-standalone-'.bin2hex(random_bytes(6));
        mkdir($root.'/grammar/8.6',0777,true);
        $data=json_decode(file_get_contents(self::root().'/php-grammar.json'),true);
        $data['versions']=array_values(array_filter($data['versions'],static fn($p)=>$p['version']==='8.6'));
        foreach(['php.ebnf','php.md']as $file)copy(self::root().'/grammar/8.6/'.$file,$root.'/grammar/8.6/'.$file);
        try {
            $manifest=RepositoryManifest::fromArray($root,$data);
            self::assertSame(['8.6'],$manifest->versions());
            self::assertSame('draft',$manifest->package('8.6')->status);
            self::assertCount(361,(new GrammarRepository($manifest))->load('8.6')->productions());
            self::assertTrue(PhpGrammarMatcher::forManifest($manifest)->matches('8.6','<?php f(?);')->matched);
            self::assertSame('grammar/8.6/changes.md',$manifest->package('8.6')->metadata['changes']);
        } finally {
            foreach(['php.ebnf','php.md']as $file)unlink($root.'/grammar/8.6/'.$file);
            rmdir($root.'/grammar/8.6');rmdir($root.'/grammar');rmdir($root);
        }
    }
}
