<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

use PhpGrammar\Ebnf\Node;
use PhpGrammar\Ebnf\Parser;

/** Maintainer validation of reviewed differences, not a language-equivalence proof. */
final class ChangeTraceability
{
    public static function structure(Node $node): array
    {
        $result = ['kind' => substr(strrchr($node::class, '\\'), 1)];
        foreach (get_object_vars($node) as $key => $value) {
            $result[$key] = $value instanceof Node ? self::structure($value)
                : (is_array($value) ? array_map(self::structure(...), $value) : $value);
        }
        return $result;
    }

    public static function sections(string $source): array
    {
        $source = preg_replace('/<!-- BEGIN GENERATED EBNF -->.*?<!-- END GENERATED EBNF -->/s', '', $source);
        $parts = preg_split('/^(## .+)$/m', $source, -1, PREG_SPLIT_DELIM_CAPTURE);
        $sections = ['preamble' => trim(array_shift($parts))];
        for ($i = 0; $i < count($parts); $i += 2) $sections[$parts[$i]] = trim($parts[$i + 1]);
        return $sections;
    }

    /** Overrides support negative regression tests without changing repository artifacts. */
    public static function validate(string $root, array $map, array $overrides = []): array
    {
        $read = static fn(string $p): string => $overrides[$p] ?? (string) file_get_contents($root . '/' . $p);
        $errors = [];
        $check = static function(bool $ok, string $message) use (&$errors): void { if (!$ok) $errors[] = $message; };
        $oldText = $read('grammar/8.5/php.ebnf'); $newText = $read('grammar/8.6/php.ebnf');
        $oldDoc = $read('grammar/8.5/php.md'); $newDoc = $read('grammar/8.6/php.md');
        $changes = $read('grammar/8.6/changes.md');
        $check(hash('sha256', $oldText) === $map['baseline']['ebnf'], 'Baseline EBNF differs from recorded copying revision');
        $check(hash('sha256', $oldDoc) === $map['baseline']['markdown'], 'Baseline specification differs from recorded copying revision');
        $check(hash('sha256', $newText) === $map['ebnfSha256'], 'Unreviewed EBNF text (including comments/formatting)');
        foreach ($map['implementation'] as $path=>$entry) {
            $check(is_file($root.'/'.$path) && hash('sha256',$read($path)) === $entry['sha256'], 'Unreviewed lexical/adapter input: '.$path);
            $check(!empty($entry['reason']), 'Missing lexical/adapter review: '.$path);
        }
        $old = (new Parser())->parse($oldText)->productionMap();
        $new = (new Parser())->parse($newText)->productionMap();
        $diff = ['added'=>[], 'removed'=>[], 'modified'=>[]];
        $details = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $name) {
            $kind = !isset($old[$name]) ? 'added' : (!isset($new[$name]) ? 'removed' :
                (self::structure($old[$name]->expression) !== self::structure($new[$name]->expression) ? 'modified' : null));
            if ($kind === null) continue;
            $diff[$kind][] = $name;
            $details[$name] = ['kind'=>$kind, 'before'=>isset($old[$name]) ? self::structure($old[$name]->expression) : null,
                'after'=>isset($new[$name]) ? self::structure($new[$name]->expression) : null];
            $entry = $map['productions'][$name] ?? null;
            $check(is_array($entry) && ($entry['kind'] ?? null) === $kind, 'Unmapped production difference: ' . $name);
            $check(($entry['before'] ?? null) === hash('sha256',json_encode($details[$name]['before'])) && ($entry['after'] ?? null) === hash('sha256',json_encode($details[$name]['after'])), 'Unreviewed production structure: '.$name);
        }
        foreach ($map['productions'] as $name=>$entry) {
            $check(isset($details[$name]), 'Stale production mapping: ' . $name);
            $check(isset($map['findings'][$entry['finding']]), 'Unknown change identifier for ' . $name);
        }
        $expected = ['Overview and comparison baseline','Added syntax','Modified syntax','Removed syntax','Lexical and tokenization changes','Contextual and compiler-enforced restrictions','Compatibility and unchanged syntax','Authoritative source references','Conformance and regression evidence','Known limitations and outstanding uncertainties'];
        preg_match_all('/^## (.+)$/m', $changes, $headings);
        $check($headings[1] === array_map(static fn($i,$h)=>($i+1).'. '.$h, array_keys($expected), $expected), 'Required ten change sections missing or reordered');
        $check(hash('sha256',$changes) === $map['changesSha256'], 'Change-document review is stale');
        foreach ($map['findings'] as $id=>$entry) {
            $check(str_contains($changes, $id), 'Missing change identifier: ' . $id);
            $check($entry['productions'] !== [] && $entry['fixtures'] !== [] && $entry['sources'] !== [], 'Missing source/production/test evidence: ' . $id);
            foreach ($entry['productions'] as $name) $check(isset($old[$name]) || isset($new[$name]), 'Invalid production reference: ' . $name);
            foreach ($entry['sources'] as $source) $check(str_contains($source, '/'.$map['pin'].'/'), 'Unpinned source: ' . $id);
            foreach ($entry['fixtures'] as $path=>$hash) $check(is_file($root.'/'.$path) && hash('sha256',$read($path)) === $hash, 'Missing/stale test reference: ' . $path);
        }
        $before = self::sections($oldDoc); $after = self::sections($newDoc); $prose = [];
        foreach (array_unique(array_merge(array_keys($before),array_keys($after))) as $section) {
            if (($before[$section] ?? null) === ($after[$section] ?? null)) continue;
            $prose[] = $section; $entry = $map['documentation'][$section] ?? [];
            $check(($entry['before'] ?? null) === hash('sha256',$before[$section] ?? '') && ($entry['after'] ?? null) === hash('sha256',$after[$section] ?? ''), 'Unreviewed documentation difference: ' . $section);
            $check(!empty($entry['reason']) && !empty($entry['findings']), 'Missing documentary review: ' . $section);
            foreach ($entry['findings'] ?? [] as $id) $check(isset($map['findings'][$id]) || in_array($id, ['integration-status','inherited-evidence-label'],true), 'Unknown documentary identifier: ' . $id);
        }
        foreach ($map['documentation'] as $section=>$entry) $check(in_array($section,$prose,true), 'Stale documentation mapping: '.$section);
        foreach ($map['openFindings'] as $id) $check(str_contains($changes,$id) && str_contains($newDoc,$id), 'Missing unresolved disposition: '.$id);
        preg_match_all('~\[[^\]]*\]\(([^)]+)\)~',$changes,$links);
        foreach ($links[1] as $link) if (!preg_match('~^https?://|^#~',$link)) $check(file_exists($root.'/grammar/8.6/'.explode('#',$link)[0]), 'Broken change-document reference: '.$link);
        preg_match('/<!-- BEGIN GENERATED EBNF -->\R```ebnf\R(.*?)\R```\R<!-- END GENERATED EBNF -->/s',$newDoc,$block);
        $check(isset($block[1]) && trim($newText) === trim($block[1]), 'EBNF/Markdown parity');
        return ['scope'=>'Reviewed structural and documentary differences; no completeness certification', 'differences'=>$diff,
            'structures'=>$details,'documentationSections'=>$prose,'openFindings'=>$map['openFindings'], 'obligations'=>$map['obligations'], 'errors'=>$errors];
    }
}
