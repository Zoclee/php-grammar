<?php
$result = $input
    |> trim(...)
    |> strtolower(...);

$value = 1 + 2 * 3 |> intval(...);
$compare = $value < $other |> boolval(...);
$fallback = $maybe ?? $default ? $a : $b;
