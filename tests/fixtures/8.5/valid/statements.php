<?php
declare(strict_types=1);

for ($i = 0, (void) tick($i); $i < 3; $i++) {
    if ($i === 1) {
        continue;
    } elseif ($i > 1) {
        break;
    } else {
        echo $i;
    }
}

switch ($i) {
    case 0:
        echo 'zero';
        break;
    default:
        echo 'other';
}

try {
    throw new RuntimeException();
} catch (RuntimeException|LogicException $e) {
    echo $e->getMessage();
} finally {
    echo 'done';
}
