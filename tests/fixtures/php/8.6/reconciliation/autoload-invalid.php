<?php
interface Root {} interface Child extends Root {} interface Maker {public function get():Child;} spl_autoload_register(static function($n){if($n==="Base"){eval("class Base {public function get():Root {return new class implements Root {};}}");}}); class C extends Base implements Maker {} echo "linked";
