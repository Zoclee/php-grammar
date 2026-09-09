<?php
declare(strict_types = 1);
include "file.php";
start:
function ids() {
    yield 1;
    yield from [2];
}
goto start;
