# Unknown property hooks are accepted inside discarded declarations

## Summary

PHP 8.5.10 and PHP 8.6.0RC4 accept an unknown property-hook name inside a closure
eliminated during compilation. Retaining the closure causes the same hook name
to be rejected. Is hook-name validation intended to depend on declaration elimination?

## Minimal reproducer

Save each example separately and run `php -n -l example.php`.

Discarded declaration:

```php
<?php
false && static function(){return new class {public int $x {unknown=>1;}};};
```

Corresponding retained declaration:

```php
<?php
$f=static function(){return new class {public int $x {unknown=>1;}};};
```

Valid `get` control:

```php
<?php
class C {public int $x {get=>1;}}
```

## Actual behaviour

Both versions accept the discarded declaration and valid `get` control (lint
exit 0, `No syntax errors detected in <file>`). Both reject the retained
declaration (exit 255), reporting on line 2:

```text
Fatal error: Unknown hook "unknown" for property class@anonymous::$x, expected "get" or "set"
```

The diagnostic is followed by the file location and a main stack frame.
The RC4 pre-optimizer dump for the discarded declaration contains only
`RETURN int(1)`; the closure and class declaration have been eliminated.
These are compilation observations, not runtime short-circuit execution or an
isolated parser test.

## Expected behaviour / clarification requested

**Must unknown property-hook names be rejected regardless of whether an enclosing
declaration survives compilation?**

If discarded acceptance is intended, what validation/elimination boundary
defines source validity? The observed difference alone does not establish
whether this is a compiler defect or intentional behavior.

## Affected versions

Observed on Windows x64 in both PHP 8.5.10 (distribution comparator) and PHP
8.6.0RC4 NTS VC2022, built from source commit
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`. The 8.5 distribution is not attested
to an exact source commit. CLI OPcache and JIT were disabled for lint;
the separate opcode dump used CLI OPcache with optimization level 0 and JIT disabled.

## Relevant implementation

- Pinned [property-hook parser productions](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_language_parser.y#L1160)
  accept `T_STRING` for the hook name.
- Pinned [`zend_get_property_hook_kind_from_name`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L9348)
  recognizes `get` and `set`; [hook compilation](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L9409)
  rejects unknown names when visited. The question is whether that validation
  should also apply to declarations eliminated before compilation reaches them.