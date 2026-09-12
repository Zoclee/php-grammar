<?php
function scannerYield(): iterable {
    yield // ?> is inside Zend's atomic yield-from token
    from [1];
}
