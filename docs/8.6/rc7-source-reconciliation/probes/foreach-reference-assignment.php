<?php
$b = [1]; foreach (($a =& $b) as &$v) {$v = 3;} echo json_encode([$a, $b]);
