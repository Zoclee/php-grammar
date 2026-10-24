<?php interface I { public function f(int $x): int; } class C implements I { public function f(int $x): int { return $x + 1; } }
