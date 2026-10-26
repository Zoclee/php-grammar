<?php
function target($first, &$value) {}
function invoke($partial = target(value: 1, first: ?)) {}
echo 'declared;';
try { invoke(); } catch (Error $error) { echo $error->getMessage(); }
