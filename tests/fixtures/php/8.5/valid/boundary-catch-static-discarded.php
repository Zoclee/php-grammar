<?php
const X = true ? 1 : static function() { try {} catch (static $e) {} };
