<?php
$x = true ? static function () { function f(...$a, $b) {} } : null;
