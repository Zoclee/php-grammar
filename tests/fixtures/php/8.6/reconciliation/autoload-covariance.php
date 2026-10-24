<?php
interface Root {} interface Child extends Root {} interface Maker {public function get():Root;} spl_autoload_register(static function($n){if($n==="Base"){eval("class Base {public function get():Child {return new class implements Child {};}}");}}); class C extends Base implements Maker {} echo "linked";
