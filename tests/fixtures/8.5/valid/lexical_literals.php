<?php
$decimal = 1_234;
$binary = 0b1010_0110;
$octal = 0o755;
$hex = 0xCAFE_F00D;
$float = 1_2.3_4e-5;
$single = 'plain string';
$double = "value: {$decimal}";
$here = <<<TXT
hello $single
TXT;
$now = <<<'TXT'
literal $single
TXT;
