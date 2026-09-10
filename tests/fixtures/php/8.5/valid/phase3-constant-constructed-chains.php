<?php
class Item {
    public string $name = 'x';
    public ?self $other = null;
}
class Box {
    public Item $child;
    public function __construct() { $this->child = new Item(); }
}
const FIRST = new Box()->child?->{'name'}[0];
const SECOND = new Box()->{'child'}->other?->{'name'};
