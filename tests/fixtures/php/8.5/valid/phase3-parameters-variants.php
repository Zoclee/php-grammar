<?php
function &f(#[A] ?string &$x = null, int ...$rest): ?string { return $x; }
