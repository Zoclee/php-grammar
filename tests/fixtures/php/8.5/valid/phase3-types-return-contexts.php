<?php
class Base {}
abstract class Child extends Base {
    abstract public function stop(): never;
    abstract public function discard(): void;
    abstract public function instance(): static;
    abstract public function current(): self;
    abstract public function base(): parent;
}
