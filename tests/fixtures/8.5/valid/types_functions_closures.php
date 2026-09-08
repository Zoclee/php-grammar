<?php
function reduce((Countable&Iterator)|array $value, ?string $label = null): mixed
{
    $mapper = static fn (int $x): int => $x + 1;
    $closure = function (&$item) use ($label): void {
        echo $label, $item;
    };
    return [$mapper, $closure, $value];
}
