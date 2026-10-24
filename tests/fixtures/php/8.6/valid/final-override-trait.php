<?php
interface I { const X=1; } trait T { #[Override] public const X=2; } class C implements I { use T; }
