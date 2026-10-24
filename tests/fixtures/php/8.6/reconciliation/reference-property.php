<?php
$b=new stdClass; ($a=&$b)->p=2; echo json_encode([$a,$b]);
