<?php
$x = false ? static function () { function f(...$a, $b) {} } : null;
