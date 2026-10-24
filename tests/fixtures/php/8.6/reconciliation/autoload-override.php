<?php
interface Root {const X=1;} interface Child extends Root {} spl_autoload_register(static function($n){if($n==="Base"){eval("class Base implements Child {}");}}); class C extends Base {#[Override] const X=2;} echo "linked";
