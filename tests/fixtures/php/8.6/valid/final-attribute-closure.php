<?php
$f = #[A] static function &(#[B] &$x) use (&$y): mixed { return $x; };
