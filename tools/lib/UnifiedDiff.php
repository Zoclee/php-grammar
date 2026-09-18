<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

/** Line matching with popular-line suppression and earliest-match tie breaking. */
final class UnifiedDiff
{
    public static function compare(array $a, array $b, string $from, string $to): array
    {
        $index = [];
        foreach ($b as $j => $line) {
            $index[$line][] = $j;
        }
        if (count($b) >= 200) {
            $index = array_filter($index, static fn(array $positions): bool => count($positions) <= intdiv(count($b), 100) + 1);
        }
        $queue = [[0, count($a), 0, count($b)]];
        $blocks = [];
        while ($queue) {
            [$alo, $ahi, $blo, $bhi] = array_pop($queue);
            $besti = $alo;
            $bestj = $blo;
            $best = 0;
            $lengths = [];
            for ($i = $alo; $i < $ahi; ++$i) {
                $next = [];
                foreach ($index[$a[$i]] ?? [] as $j) {
                    if ($j < $blo) {
                        continue;
                    }
                    if ($j >= $bhi) {
                        break;
                    }
                    $size = $next[$j] = ($lengths[$j - 1] ?? 0) + 1;
                    if ($size > $best) {
                        [$besti, $bestj, $best] = [$i - $size + 1, $j - $size + 1, $size];
                    }
                }
                $lengths = $next;
            }
            while ($besti > $alo && $bestj > $blo && $a[$besti - 1] === $b[$bestj - 1]) {
                --$besti;
                --$bestj;
                ++$best;
            }
            while ($besti + $best < $ahi && $bestj + $best < $bhi && $a[$besti + $best] === $b[$bestj + $best]) {
                ++$best;
            }
            if ($best) {
                $blocks[] = [$besti, $bestj, $best];
                if ($alo < $besti && $blo < $bestj) {
                    $queue[] = [$alo, $besti, $blo, $bestj];
                }
                if ($besti + $best < $ahi && $bestj + $best < $bhi) {
                    $queue[] = [$besti + $best, $ahi, $bestj + $best, $bhi];
                }
            }
        }
        sort($blocks);
        $merged = [];
        foreach ($blocks as [$i, $j, $size]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $merged[$last][0] + $merged[$last][2] === $i && $merged[$last][1] + $merged[$last][2] === $j) {
                $merged[$last][2] += $size;
            } else {
                $merged[] = [$i, $j, $size];
            }
        }
        $merged[] = [count($a), count($b), 0];
        $codes = [];
        $i = $j = 0;
        foreach ($merged as [$ai, $bj, $size]) {
            if ($i < $ai || $j < $bj) {
                $codes[] = [$i < $ai ? ($j < $bj ? 'replace' : 'delete') : 'insert', $i, $ai, $j, $bj];
            }
            if ($size) {
                $codes[] = ['equal', $ai, $ai + $size, $bj, $bj + $size];
            }
            [$i, $j] = [$ai + $size, $bj + $size];
        }
        if (!$codes) {
            return [];
        }
        if ($codes[0][0] === 'equal') {
            $codes[0][1] = max($codes[0][1], $codes[0][2] - 3);
            $codes[0][3] = max($codes[0][3], $codes[0][4] - 3);
        }
        $last = count($codes) - 1;
        if ($codes[$last][0] === 'equal') {
            $codes[$last][2] = min($codes[$last][2], $codes[$last][1] + 3);
            $codes[$last][4] = min($codes[$last][4], $codes[$last][3] + 3);
        }
        $groups = $group = [];
        foreach ($codes as [$tag, $i1, $i2, $j1, $j2]) {
            if ($tag === 'equal' && $i2 - $i1 > 6) {
                $group[] = [$tag, $i1, $i1 + 3, $j1, $j1 + 3];
                $groups[] = $group;
                $group = [];
                [$i1, $j1] = [$i2 - 3, $j2 - 3];
            }
            $group[] = [$tag, $i1, $i2, $j1, $j2];
        }
        if ($group && !(count($group) === 1 && $group[0][0] === 'equal')) {
            $groups[] = $group;
        }
        if (!$groups) {
            return [];
        }
        $output = ['--- ' . $from, '+++ ' . $to];
        foreach ($groups as $group) {
            $first = $group[0];
            $last = $group[count($group) - 1];
            $output[] = '@@ -' . self::range($first[1], $last[2]) . ' +' . self::range($first[3], $last[4]) . ' @@';
            foreach ($group as [$tag, $i1, $i2, $j1, $j2]) {
                if ($tag === 'equal') {
                    foreach (array_slice($a, $i1, $i2 - $i1) as $line) {
                        $output[] = ' ' . $line;
                    }
                } else {
                    if ($tag === 'replace' || $tag === 'delete') {
                        foreach (array_slice($a, $i1, $i2 - $i1) as $line) {
                            $output[] = '-' . $line;
                        }
                    }
                    if ($tag === 'replace' || $tag === 'insert') {
                        foreach (array_slice($b, $j1, $j2 - $j1) as $line) {
                            $output[] = '+' . $line;
                        }
                    }
                }
            }
        }
        return $output;
    }

    private static function range(int $start, int $end): string
    {
        $length = $end - $start;
        return $length === 1 ? (string) ($start + 1) : ($length === 0 ? $start : $start + 1) . ',' . $length;
    }
}
