<?php
trait T { function f() {} } class C { use T { f as yield; f as match; f as fn; f as clone; f as isset; f as unset; f as echo; } }
