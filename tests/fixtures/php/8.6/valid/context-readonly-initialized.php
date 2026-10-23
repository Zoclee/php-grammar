<?php
class C {public readonly int $p=1;} $o=new C; try {$o->p=2;} catch (Error $e) {echo $e->getMessage();}
