<?php
$object = new class() {
    public function value(): int {
        return 1;
    }
};
echo $object->value();
