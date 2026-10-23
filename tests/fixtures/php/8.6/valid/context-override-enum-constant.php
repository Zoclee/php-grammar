<?php
interface A {const X=1;} enum E implements A {case C; #[\Override] const X=2;}
