<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PHPUnit\Framework\TestCase;
use PhpGrammar\Tools\ManifestValidator;
use PhpGrammar\Tools\Support;
require_once __DIR__ . '/../tools/lib/Support.php';
require_once __DIR__ . '/../tools/lib/ManifestValidator.php';

final class ToolManifestTest extends TestCase
{
    private function manifest(): \stdClass
    {
        return json_decode(file_get_contents(__DIR__ . '/../php-grammar.json'), false, 512, JSON_THROW_ON_ERROR);
    }

    public function testCurrentAndLegacy(): void
    {
        $validator = new ManifestValidator();
        self::assertSame([], $validator->validate($this->manifest()));
        self::assertSame([], $validator->validate((object) ['versions' => $this->manifest()->versions]));
    }

    public function testInvalidFields(): void
    {
        foreach ([['version', '8.5.1'], ['grammar', '../outside'], ['grammar', '/absolute'], ['grammar', 'C:/drive'], ['grammar', 'grammar\\file'], ['grammar', 'grammar/./file'], ['grammar', 'missing.ebnf'], ['status', 'unknown'], ['phpSource', (object) ['branch' => 'PHP-8.5', 'commit' => 'HEAD']], ['metadata', (object) ['bad' => '../outside']]] as [$field, $value]) {
            $manifest = $this->manifest();
            $manifest->versions[0]->$field = $value;
            self::assertNotEmpty((new ManifestValidator())->validate($manifest), $field);
        }
    }

    public function testStructureAndPrimitives(): void
    {
        foreach ([['schemaVersion', '2.0'], ['versions', []], ['lexicalPrimitives', ['code-unit', 'code-unit']], ['lexicalPrimitiveDefinitions', (object) ['code-unit' => (object) ['meaning' => 'x']]]] as [$key, $value]) {
            $manifest = $this->manifest();
            $manifest->$key = $value;
            self::assertNotEmpty((new ManifestValidator())->validate($manifest), $key);
        }
        $manifest = $this->manifest();
        $manifest->versions[] = clone $manifest->versions[0];
        self::assertNotEmpty((new ManifestValidator())->validate($manifest));
    }

    public function testSchemaRulesBeyondRuntimeManifestChecks(): void
    {
        foreach (['rootProduction' => 'Bad', 'lexer' => '8.5.1', 'sourceProfile' => '', 'phpSource' => (object) ['branch' => '', 'commit' => str_repeat('a', 40)], 'metadata' => [], 'grammar' => 'docs'] as $field => $value) {
            $manifest = $this->manifest();
            $manifest->versions[0]->$field = $value;
            self::assertNotEmpty((new ManifestValidator())->validate($manifest), $field);
        }
        foreach (['lexicalPrimitives' => ['Bad'], 'lexicalPrimitiveDefinitions' => [], 'versions' => (object) ['0' => $this->manifest()->versions[0]]] as $field => $value) {
            $manifest = $this->manifest();
            $manifest->$field = $value;
            self::assertNotEmpty((new ManifestValidator())->validate($manifest), $field);
        }
        $manifest = $this->manifest();
        $manifest->lexicalPrimitiveDefinitions->{'code-unit'}->meaning = '';
        self::assertNotEmpty((new ManifestValidator())->validate($manifest));
        self::assertNotEmpty((new ManifestValidator())->validate([]));
        self::assertNotEmpty((new ManifestValidator())->validate($this->manifest(), sys_get_temp_dir()));
    }
}
