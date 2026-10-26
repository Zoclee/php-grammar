<?php
function target(&$value) {}
function invoke($partial = target(1, ...)) {}
echo 'declared;';
try { invoke(); } catch (Error $error) { echo $error->getMessage(); }
