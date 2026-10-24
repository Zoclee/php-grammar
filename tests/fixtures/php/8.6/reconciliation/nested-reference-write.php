<?php
$c=[1]; ($a=&($b=&$c))[0]=2; echo json_encode([$a,$b,$c]);
