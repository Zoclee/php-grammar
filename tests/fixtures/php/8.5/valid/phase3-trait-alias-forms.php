<?php
trait T { function f() {} } class C { use T { f as match; T::f as protected; f as final alias; } }
