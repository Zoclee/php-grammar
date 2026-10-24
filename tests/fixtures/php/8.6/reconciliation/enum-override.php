<?php
interface I {const A=1;} enum E:int implements I {#[Override] case A=2;} echo E::A->value;
