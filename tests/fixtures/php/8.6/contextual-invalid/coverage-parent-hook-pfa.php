<?php
class C { public int $x { set(int $value) {} } } class D extends C { public int $x { set(int $value) { $p=parent::$x::set(?); } } }
