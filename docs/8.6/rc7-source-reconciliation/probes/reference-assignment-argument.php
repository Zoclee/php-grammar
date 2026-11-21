<?php
function f(&$x) {$x[] = 9;} $b = [1]; f($a =& $b); echo json_encode([$a, $b]);
