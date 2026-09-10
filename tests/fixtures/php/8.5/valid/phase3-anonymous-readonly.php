<?php
$object = new readonly class(1) {
    public function __construct(public int $value) {}
};
