<?php const X = strlen(...); class C { public $x = strlen(...); const X = strlen(...); } function f($x = strlen(...)) {} #[A(strlen(...))] class D {}
