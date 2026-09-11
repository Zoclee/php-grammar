<?php function f(){
yield throw $x => 1;
yield throw throw $x => 1;
yield fn() => $x => 1;
yield include $x => 1;
yield print $x => 1;
yield throw $a or $b xor $c and $d => 1;
yield throw print $x => 1;
yield throw include $x => 1;
yield throw fn() => $x => 1;
yield throw $a or throw $b => 1;
yield throw $a xor throw $b => 1;
yield throw $a and throw $b => 1;
yield throw print throw $x => 1;
yield throw include throw $x => 1;
yield throw fn() => throw $x => 1;
yield print yield => 1;
yield throw yield => 1;
yield throw yield $x => $y => 1;
yield throw $a + yield $b => $c => 1;
}
