<?php
function f() { global $a, $$b; static $x=1, $y; return $x; }
