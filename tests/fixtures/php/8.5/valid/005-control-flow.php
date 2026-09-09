<?php
if ($ok) {
    echo 1;
} elseif ($other) {
    echo 2;
} else {
    echo 3;
}

for ($i = 0; $i < 3; $i++) {
    continue;
}

while ($ok) {
    break;
}
