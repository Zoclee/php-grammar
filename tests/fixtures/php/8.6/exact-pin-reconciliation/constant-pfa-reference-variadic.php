<?php
function target(&...$values) {}
function invoke($partial = target(extra: 1, ...)) {}
echo 'declared;';
try { invoke(); } catch (Error $error) { echo $error->getMessage(); }
