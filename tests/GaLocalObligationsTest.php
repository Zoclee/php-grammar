<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
require_once __DIR__ . '/../tools/lib/GaLocalObligations.php';
use PhpGrammar\Tools\GaLocalObligations as L;
use PHPUnit\Framework\TestCase;

final class GaLocalObligationsTest extends TestCase
{
    private function sources(): array {
        return [
            'Zend/zend_compile.c' => "#include \"zend.h\"\n#include \"zend_types.h\"\n#include \"zend_portability.h\"\n#include \"zend_string.h\"\n",
            'Zend/zend.h' => "#ifndef ZEND_H\n#define ZEND_H\n#include \"zend_types.h\"\n#include \"zend_string.h\"\n#endif\n",
            'Zend/zend_types.h' => "#ifndef ZEND_TYPES_H\n#define ZEND_TYPES_H\n#include \"zend_portability.h\"\n#endif\n",
            'Zend/zend_portability.h' => "#ifndef ZEND_PORTABILITY_H\n#define ZEND_PORTABILITY_H\n#endif\n",
            'Zend/zend_string.h' => "#ifndef ZEND_STRING_H\n#define ZEND_STRING_H\n#endif\n",
        ];
    }
    public function testIncludeProofRequiresTheActualGuardedTransitivePath(): void {
        $sources = $this->sources(); self::assertSame([], L::includeErrors($sources));
        foreach (['zend.h', 'zend_types.h', 'zend_portability.h', 'zend_string.h'] as $header) {
            $bad = $sources; $bad['Zend/' . $header] = '';
            self::assertNotSame([], L::includeErrors($bad));
        }
        $sources['Zend/zend_compile.c'] = str_replace('#include "zend.h"', '', $sources['Zend/zend_compile.c']);
        self::assertContains('Missing prior zend.h include: zend_types.h', L::includeErrors($sources));
    }
    public function testGuardResetPreventsDischargingTheHunk(): void {
        $sources = $this->sources(); $sources['Zend/another.h'] = "#undef ZEND_TYPES_H\n";
        self::assertContains('Include guard reset: Zend/another.h', L::includeErrors($sources));
    }
}
