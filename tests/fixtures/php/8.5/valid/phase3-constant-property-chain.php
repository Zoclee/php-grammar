<?php
enum E { case A; } const X = E::A?->{"name"}[0], Y = E::A->{"name"};
