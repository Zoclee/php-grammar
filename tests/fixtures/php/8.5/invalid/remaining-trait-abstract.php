<?php trait T { function foo() {} } class C { use T { foo as abstract bar; } }
