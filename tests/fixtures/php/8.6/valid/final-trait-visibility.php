<?php
trait T { function f(){} } class C { use T { f as protected; f as private g; } }
