<?php function f(){ yield yield 1; yield 1 => yield 2; yield yield 1 => 2; yield yield 1 => 2 => 3; yield print 1; yield 1 => print 2; yield from yield 1; }
