<?php
$f = static function &($x = 1) use ($outer, &$shared,) { return $shared; };
