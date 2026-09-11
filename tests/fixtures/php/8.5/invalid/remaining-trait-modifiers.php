<?php trait T { function foo() {} } class C { use T { foo as public final bar; } }
