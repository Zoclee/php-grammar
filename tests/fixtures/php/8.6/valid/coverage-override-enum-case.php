<?php
interface I { const X=1; } enum E implements I { #[\Override] case X; }
