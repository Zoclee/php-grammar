<?php
interface A { const X=1; } interface B extends A { #[\Override] const X=2; } class C implements B { #[\Override] const X=3; }
