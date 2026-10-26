<?php
interface ParentInterface { public function make(): ParentType; }
interface ChildInterface extends ParentInterface {}
spl_autoload_register(function ($name) {
    static $depth = 0;
    if (++$depth > 3) { throw new LogicException('bounded autoload exceeded'); }
    try {
        if ($name === 'ChildType') {
            echo class_exists('Implementation') ? 'visible;' : 'hidden;';
            eval('class ChildType extends ParentType {}');
        } elseif ($name === 'ParentType') {
            eval('class ParentType {}');
        }
    } finally { --$depth; }
});
class Implementation implements ChildInterface {
    public function make(): ChildType { return new ChildType; }
}
echo (new Implementation)->make() instanceof ParentType ? 'linked' : 'not-linked';
