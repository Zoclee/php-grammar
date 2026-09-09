<?php
$items = [1, 2, "three"];
$name = match ($items[0]) {
    1 => "one",
    default => "other",
};
