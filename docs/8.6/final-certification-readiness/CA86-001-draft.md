# Unpublished maintainer discussion draft: CA86-001

Should an unknown property-hook name in a discarded declaration be accepted?

Prepared October 26, 2026 from retained Phase 7C.4 observations. Not submitted; no maintainer contacted. No experiment rerun for this draft.

PHP 8.6 source: `f23b4c222cebe33be93ee8ecaaa14390c5507a08`; exact executable SHA-256 `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6`. PHP 8.5 comparator is the recorded 8.5.10 distribution, not an asserted exact-pin build. Original commands, raw diagnostics and binary/configuration identity are retained in [observations](../exact-pin-blocker-reconciliation/probes-verified.json).

## Minimal reproducer and adjacent controls

### `hook-live`

```php
<?php
class C {public int $x {unknown=>1;}}
```

Fixture `tests/fixtures/php/8.6/reconciliation/hook-live.php`; SHA-256 `29448c72c108ac7be43bcf0317e6e57b71d0b3663997c8039c4362ad680da0c2`.

- PHP 8.5 distribution: lint exit 255; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 255; no runtime execution recorded.
- PHP 8.6 legacy RC4 distribution: lint exit 255; no runtime execution recorded. Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

### `hook-retained`

```php
<?php
$f=static function(){return new class {public int $x {unknown=>1;}};};
```

Fixture `tests/fixtures/php/8.6/reconciliation/hook-retained.php`; SHA-256 `a33c67d3ffc9bc91bfec583c16dd47be932697d8aaa10a1689c8b41c0e2d4e91`.

- PHP 8.5 distribution: lint exit 255; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 255; no runtime execution recorded.
- PHP 8.6 legacy RC4 distribution: lint exit 255; no runtime execution recorded. Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

### `hook-discarded`

```php
<?php
false && static function(){return new class {public int $x {unknown=>1;}};};
```

Fixture `tests/fixtures/php/8.6/reconciliation/hook-discarded.php`; SHA-256 `bcaf8bba8d2c705c8added05cf1e8e2f93889079479c65609d20b7d1dd3ddd76`.

- PHP 8.5 distribution: lint exit 0; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 0; no runtime execution recorded.
- PHP 8.6 legacy RC4 distribution: lint exit 0; no runtime execution recorded. Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

### `hook-constant-discarded`

```php
<?php
const F=true ? 1 : static function(){return new class {public int $x {unknown=>1;}};};
```

Fixture `tests/fixtures/php/8.6/reconciliation/hook-constant-discarded.php`; SHA-256 `3d080c726c263fc00fcbea95105af9af2ac280d2e1aac87d96a76754a89863a5`.

- PHP 8.5 distribution: lint exit 0; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 0; no runtime execution recorded.
- PHP 8.6 legacy RC4 distribution: lint exit 0; no runtime execution recorded. Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

### `hook-get-repair`

```php
<?php
class C {public int $x {get=>1;}}
```

Fixture `tests/fixtures/php/8.6/reconciliation/hook-get-repair.php`; SHA-256 `afdac1044ee3e1745054f81b4e80ed35f40751707ad8ea012031a9f2d399e2de`.

- PHP 8.5 distribution: lint exit 0; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 0; no runtime execution recorded.
- PHP 8.6 legacy RC4 distribution: lint exit 0; no runtime execution recorded. Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

## Evidence and question

Both PHP 8.5 and exact RC4 reject live/retained unknown hooks, accept the two discarded controls, and accept the get repair. The repository rejects unknown names even when discarded. The ordinary discarded dump contains only RETURN 1; the constant witness is a ternary initializer. An independent PHP 8.6 scope decision or separately authorized correction is needed; PHP 8.5 must not be silently changed.

Relevant upstream discussion: No issue specifically deciding this intent is retained; source question for the PHP property-hook/compiler maintainers.

Pinned source: Zend/zend_language_parser.y:1160-1169 accepts T_STRING; Zend/zend_compile.c:9348 names zend_get_property_hook_kind_from_name, with unknown-name rejection at 9409-9414. Repository grammar/8.6/php.ebnf:788-795 restricts hook-name to get/set.

**Requested clarification:** Is acceptance of an unknown hook inside an eliminated closure intended source validity, or should hook-name validity be checked before elimination? Does the same intended rule apply to a discarded ternary constant-initializer arm? This question concerns these exact forms, not every constant-folding path.

Expected behavior is deliberately open pending an authoritative intended-validity decision. A source change, if required, must be tied to an immutable reviewed successor and discriminating regression tests before any grammar/specification action. The retained 26 writable controls / 31 traversal controls / five paired hook controls supply the broader comparison; counts are distinct, not one combined certification matrix.

The latest retained bounded PR refresh is Phase 7C.6; this draft makes no fresh upstream-search or absence claim. Reproduction commands are preserved per observation in [the draft evidence index](upstream-draft-evidence.json). Lint combines parsing and compilation. The separate non-executing opcode profile enables CLI OPcache dumps with optimization level zero and JIT disabled; it is not optimized-runtime evidence.
