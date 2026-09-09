<?php
function map(int|string $value): ?string {
    return (string) $value;
}

$fn = static fn (A&B $value): A|B => $value;
