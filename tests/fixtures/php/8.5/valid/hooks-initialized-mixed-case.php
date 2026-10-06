<?php
class Example
{
    private bool $modified = false;

    public string $foo = 'default value' {
        GeT => $this->foo . ($this->modified ? ' (modified)' : '');

        sEt(string $value) {
            $this->foo = strtolower($value);
            $this->modified = true;
        }
    }
}
