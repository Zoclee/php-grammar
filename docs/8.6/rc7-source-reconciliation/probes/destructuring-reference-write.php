<?php
$arr = [[1]]; ([&$x] = $arr)[0] = 5; echo json_encode([$arr, $x]);
