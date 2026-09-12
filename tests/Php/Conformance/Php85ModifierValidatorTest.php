<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\Php85ModifierValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php85ModifierValidatorTest extends TestCase
{
    #[DataProvider('cases')]
    public function testParserActionCategories(string $target, array $tokens, ?string $category): void
    {
        self::assertSame($category === null ? null : 'php85.modifier.' . $category,
            (new Php85ModifierValidator())->validate($target, $tokens));
    }

    public static function cases(): iterable
    {
        yield ['class', ['readonly', 'final'], null];
        yield ['class', ['abstract', 'final'], 'abstract-final'];
        yield ['class', ['final', 'abstract'], 'abstract-final'];
        yield ['anonymous-class', ['abstract'], 'target'];
        yield ['anonymous-class', ['final'], 'target'];
        yield ['anonymous-class', ['readonly', 'readonly'], 'duplicate'];
        yield ['property', ['PUBLIC', 'private(set)', 'readonly'], null];
        yield ['property', ['public', 'public'], 'visibility'];
        yield ['property', ['private', 'protected'], 'visibility'];
        yield ['property', ['public(set)', 'private(set)'], 'set-visibility'];
        yield ['property', ['abstract', 'final'], 'abstract-final'];
        yield ['method', ['static', 'static'], 'duplicate'];
        yield ['method', ['abstract', 'final'], 'abstract-final'];
        yield ['method', ['readonly'], 'target'];
        yield ['parameter', ['final', 'private(set)'], null];
        yield ['parameter', ['abstract'], 'target'];
        yield ['parameter', ['static'], 'target'];
        yield ['hook', ['final', 'final'], 'duplicate'];
        yield ['hook', ['public'], 'target'];
        yield ['class-constant', ['public', 'final'], null];
        yield ['class-constant', ['readonly'], 'target'];
        yield ['trait-alias', ['static'], null];
        yield ['trait-alias', ['abstract'], null];
        yield ['trait-alias', ['readonly'], 'target'];
        yield ['method', [], null];
    }

    public function testUnknownTargetIsNotSilentlyAccepted(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Php85ModifierValidator())->validate('8.6', []);
    }
}
