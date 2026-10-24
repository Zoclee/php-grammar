<?php
class B {} class C extends B { function a(): static { return $this; } function b(): parent { return $this; } function c(): self { return $this; } function d(): true { return true; } function e(): false { return false; } function f(): null { return null; } function g(): never { throw new Exception; } }
