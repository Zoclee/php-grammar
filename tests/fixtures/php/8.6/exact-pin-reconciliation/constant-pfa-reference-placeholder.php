<?php
function target(&$value) { $value++; }
function factory($partial = target(?)) { return $partial; }
$value = 1;
factory()($value);
echo $value;
