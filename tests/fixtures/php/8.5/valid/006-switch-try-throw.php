<?php
try {
    switch ($value) {
        case 1:
            throw $error;
        default:
            echo 0;
    }
} catch (Error $e) {
    echo 1;
} finally {
    echo 2;
}
