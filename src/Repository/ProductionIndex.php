<?php

declare(strict_types=1);

namespace PhpGrammar\Repository;

use PhpGrammar\Ebnf\Grammar;

/** Navigation derived from canonical section comments and parsed productions. */
final readonly class ProductionIndex
{
    /** @param array<string, array{title: string, productions: list<string>}> $sections */
    private function __construct(private Grammar $grammar, private array $sections)
    {
    }

    public static function fromSource(Grammar $grammar, string $source): self
    {
        preg_match_all('/^\(\* SECTION (\d+) ([a-z-]+): ([^\r\n]+) \*\)\r?$/m', $source, $markers, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $sections = [];
        $starts = [];
        foreach ($markers as $marker) {
            $key = $marker[2][0];
            if (isset($sections[$key])) {
                throw new \UnexpectedValueException(sprintf('Duplicate semantic section "%s".', $key));
            }
            $sections[$key] = ['title' => $marker[3][0], 'productions' => []];
            $starts[$key] = substr_count(substr($source, 0, $marker[0][1]), "\n") + 1;
        }
        foreach ($grammar->productions() as $production) {
            $section = null;
            foreach ($starts as $key => $line) {
                if ($line >= $production->line) break;
                $section = $key;
            }
            if ($section !== null) $sections[$section]['productions'][] = $production->name;
        }
        return new self($grammar, $sections);
    }

    /** @return array<string, array{title: string, productions: list<string>}> Source order. */
    public function sections(): array
    {
        return $this->sections;
    }

    /** @return list<string> */
    public function rulesInSection(string $section): array
    {
        return $this->sections[$section]['productions']
            ?? throw new \OutOfBoundsException(sprintf('Unknown semantic section "%s".', $section));
    }

    public function sectionForRule(string $rule): ?string
    {
        $this->grammar->production($rule);
        foreach ($this->sections as $key => $section) {
            if (in_array($rule, $section['productions'], true)) return $key;
        }
        return null;
    }
}
