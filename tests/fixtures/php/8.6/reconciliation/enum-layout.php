<?php
enum E:int {case A=1; const M=99; case B=2;} echo json_encode(array_map(static fn($x)=>[$x->name,$x->value],E::cases()));
