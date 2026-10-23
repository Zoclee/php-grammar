<?php
interface A {const X=1;} interface B extends A {#[\Override] const X=2;}
