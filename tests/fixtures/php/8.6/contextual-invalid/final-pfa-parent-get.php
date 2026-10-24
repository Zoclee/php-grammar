<?php
class P { public int $p { get => 1; } } class C extends P { public int $p { get { return parent::$p::get(...,); } } }
