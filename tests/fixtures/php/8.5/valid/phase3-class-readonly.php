<?php
readonly class Base {}
final readonly class Child extends Base {
    public function __construct(public int $value) {}
}
