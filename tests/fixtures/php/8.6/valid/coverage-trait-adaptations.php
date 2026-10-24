<?php
trait T { function m() {} } trait U { function m() {} } class C { use T,U { T::m insteadof U; T::m as protected alias; } }
