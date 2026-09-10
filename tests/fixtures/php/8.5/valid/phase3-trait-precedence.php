<?php
trait T { function f() {} } trait U { function f() {} } class C { use T, U { T::f insteadof U; U::f as other; } }
