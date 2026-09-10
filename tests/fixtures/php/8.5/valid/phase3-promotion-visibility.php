<?php
class C { function __construct(protected int $x, private string $y, public readonly int $z, public protected(set) int $a, public private(set) int $b, public public(set) int $c) {} }
