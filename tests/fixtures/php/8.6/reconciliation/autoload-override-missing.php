<?php
spl_autoload_register(static function($n){if($n==="Base"){eval("class Base {}");}}); class C extends Base {#[Override] const X=2;} echo "linked";
