<?php

declare(strict_types=1);

/** Maintenance metadata is derived from plain EBNF comments, never loaded by consumers. */
final class Php85GrammarSections
{
    /** @return array<string, array{title: string, productions: list<string>}> */
    public static function read(string $source): array
    {
        preg_match_all('/^\(\* SECTION (\d+) ([a-z-]+): ([^\r\n]+) \*\)$/m', $source, $markers, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $sections = [];
        foreach ($markers as $index => $marker) {
            $key = $marker[2][0];
            if ((int) $marker[1][0] !== $index + 1 || isset($sections[$key])) {
                throw new RuntimeException('Grammar section numbers and identifiers must be unique and ordered.');
            }
            $start = $marker[0][1] + strlen($marker[0][0]);
            $end = $markers[$index + 1][0][1] ?? strlen($source);
            preg_match_all('/^([a-z][a-z-]*) =/m', substr($source, $start, $end - $start), $names);
            $sections[$key] = ['title' => $marker[3][0], 'productions' => $names[1]];
        }
        return $sections;
    }

    public static function index(string $source): string
    {
        $lines = ['<!-- BEGIN GENERATED PRODUCTION INDEX -->', '## Production index', '',
            'Grouped in canonical section order. Each name can be searched in the EBNF block below;',
            'the same numbered section headings appear in `php.ebnf`.', ''];
        foreach (self::read($source) as $section) {
            $lines[] = '### ' . $section['title'];
            $lines[] = '';
            $lines[] = wordwrap(implode(', ', array_map(
                static fn (string $name): string => '`' . $name . '`', $section['productions'])) . '.', 100);
            $lines[] = '';
        }
        $lines[] = '<!-- END GENERATED PRODUCTION INDEX -->';
        return implode("\n", $lines);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    echo json_encode(Php85GrammarSections::read(file_get_contents(dirname(__DIR__, 2) . '/grammar/8.5/php.ebnf')),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
}
