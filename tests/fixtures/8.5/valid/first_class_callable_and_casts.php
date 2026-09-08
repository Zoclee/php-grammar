<?php
function local_length(string $value): int
{
    return strlen($value);
}

#[Attribute]
class CallbackAttribute
{
    public function __construct(public Closure $first, public Closure $second) {}
}

#[CallbackAttribute(static function (string $value): string {
    return strtoupper($value);
}, local_length(...))]
class UsesConstantExpressionCallbacks {}

$callable = local_length(...);
$closure = static fn (int $x): int => $x + 1;
$casted = (int) "42";
