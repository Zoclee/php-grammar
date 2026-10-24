<?php
trait T { function m() {} } class C { use T { m as renamed; m as private; } }
