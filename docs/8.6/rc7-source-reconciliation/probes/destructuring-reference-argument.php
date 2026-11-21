<?php
function f(&$x) {$x[] = 9;} $arr = [[1]]; f([&$x] = $arr); echo json_encode($arr);
