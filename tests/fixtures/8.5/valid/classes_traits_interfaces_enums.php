<?php
interface Named
{
    public function name(): string;
}

trait Slugged
{
    public function slug(): string
    {
        return strtolower($this->name);
    }
}

final class Article implements Named
{
    use Slugged;

    public function __construct(
        final public string $name,
    ) {}

    private(set) static int $count = 0;

    public string $title {
        get => $this->name;
        set(string $value) { $this->name = $value; }
    }

    public function name(): string
    {
        return $this->name;
    }
}

enum Status: string implements Named
{
    case Draft = 'draft';
    case Published = 'published';

    public function name(): string
    {
        return $this->value;
    }
}
