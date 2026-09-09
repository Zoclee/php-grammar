<?php
#[Example]
final class User extends Person implements Named {
    public const KIND = "user";
    public string $name;
    public function name(): string {
        return $this->name;
    }
}

interface Named {
    public function name(): string;
}

trait NamedTrait {
    public function label(): string {
        return "label";
    }
}

enum Status: string {
    case Active = "active";
}
