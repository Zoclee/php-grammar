<?php
declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Conformance\VersionBoundaryCase;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php86ContextualChangesTest extends TestCase
{
    private static function root(): string {return dirname(__DIR__,3);}
    private static function json(string $path): array {
        return json_decode(file_get_contents(self::root().'/'.$path),true,512,JSON_THROW_ON_ERROR);
    }
    private static function matcher(): PhpGrammarMatcher
    {
        return PhpGrammarMatcher::forRepositoryRoot(self::root());
    }
    public static function cases(): iterable {
        foreach(self::json('tests/fixtures/php/8.6/context-cases.json')as $case)yield $case['id']=>[$case];
    }
    #[DataProvider('cases')]
    public function testStructuralAcceptanceIsSeparateFromContext(array $case): void {
        $source=file_get_contents(self::root().'/'.$case['fixture']);
        foreach(['8.5','8.6']as $version){
            $result=self::matcher()->matches($version,$source);
            self::assertTrue($result->matched,$case['id'].' '.$version.': '.implode(', ',$result->expected));
        }
    }
    #[DataProvider('cases')]
    public function testRecordedEvidenceHasCorrectStageAndFixture(array $case): void {
        $records=self::json('docs/8.6/context-validation.json')['observations'];
        $records=array_values(array_filter($records,static fn($r)=>$r['id']===$case['id']));
        self::assertCount(2,$records);
        foreach($records as $record){
            self::assertSame($case['stage'],$record['stage']);
            self::assertSame($case['finding'],$record['finding']);
            self::assertSame(hash_file('sha256',self::root().'/'.$case['fixture']),$record['fixtureSha256']);
            self::assertSame($case['accept'.str_replace('.','',$record['version'])],$record['result']['exit']===0);
        }
    }
    public function testNoEbnfChangeAndMarkdownParity(): void {
        $lock=self::json('tools/8.6/context-source-lock.json');
        $source=file_get_contents(self::root().'/grammar/8.6/php.ebnf');
        self::assertSame($lock['grammarBefore'],hash('sha256',$source));
        $doc=file_get_contents(self::root().'/grammar/8.6/php.md');
        self::assertSame(1,preg_match('/<!-- BEGIN GENERATED EBNF -->\R```ebnf\R(.*?)\R```\R<!-- END GENERATED EBNF -->/s',$doc,$match));
        self::assertSame(trim($source),trim($match[1]));
    }
    public function testContextualBoundaryMetadataUsesTheDeclaredStage(): void {
        $cases=array_column(self::json('tests/fixtures/php/8.6/context-cases.json'),null,'id');
        $paths=glob(self::root().'/tests/fixtures/version-boundaries/contextual-8.6/*.json');
        self::assertCount(7,$paths);
        foreach($paths as $path){
            $data=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
            $boundary=VersionBoundaryCase::fromArray($data,dirname($path));
            $case=$cases[basename($path,'.json')];
            self::assertSame($case['stage'],$data['validationStage']);
            self::assertSame(file_get_contents(self::root().'/'.$case['fixture']),$boundary->source);
            self::assertSame($case['accept85'],$boundary->shouldAccept('8.5'));
            self::assertSame($case['accept86'],$boundary->shouldAccept('8.6'));
        }
    }
}
