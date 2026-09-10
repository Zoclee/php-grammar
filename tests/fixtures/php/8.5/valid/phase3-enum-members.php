<?php
trait T { function f() {} } enum E { use T; const int X = 1; case A; public function g(): self { return self::A; } }
