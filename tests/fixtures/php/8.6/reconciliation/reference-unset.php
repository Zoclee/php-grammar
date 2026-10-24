<?php
$b=[1]; unset(($a=&$b)[0]); echo json_encode([$a,$b]);
