<?php trait T { function foo() {} } class C { use T { foo as public a; foo as protected b; foo as private c; foo as final d; } }
