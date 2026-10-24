<?php
class C { public int $x { set(int $value) {} } } class D extends C { public int $x { set(int $value) { parent::$x::set($value); } } }
