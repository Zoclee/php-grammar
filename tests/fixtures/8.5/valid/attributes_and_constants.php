<?php
#[Deprecated]
const OLD_FLAG = true;

#[NoDiscard]
function compute(): int
{
    return 1;
}

(void) compute();

#[DelayedTargetValidation]
#[Deprecated]
class Marked {}
