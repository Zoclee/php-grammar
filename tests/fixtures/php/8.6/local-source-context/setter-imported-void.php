<?php
namespace LocalEvidence;
use stdClass as Imported;
class X {
    public Imported $implicit { set {} }
    public Imported $explicit { set(Imported $value) {} }
}
foreach (['implicit', 'explicit'] as $name) {
    $property = new \ReflectionProperty(X::class, $name);
    echo $name, ':', $property->getHook(\PropertyHookType::Set)->getReturnType(), "\n";
}