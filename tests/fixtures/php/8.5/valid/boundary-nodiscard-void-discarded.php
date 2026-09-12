<?php
const X = true ? 1 : static function() { #[NoDiscard] function f(): void {} };
