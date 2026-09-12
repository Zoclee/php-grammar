<?php
const X = true ? 1 : static function() { goto here; while (false) { here:; } };
