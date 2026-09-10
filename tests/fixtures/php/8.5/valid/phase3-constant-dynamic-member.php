<?php
class C { const X = 1; static function f() {} } const X = C::{"X"}, Y = C::{"f"}(...);
