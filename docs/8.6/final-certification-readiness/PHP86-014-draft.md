# Unpublished maintainer discussion draft: PHP86-014

Assignment-derived writable targets: reject at compilation or emit supported writable operands?

Prepared October 26, 2026 from retained Phase 7C.4 observations. Not submitted; no maintainer contacted. No experiment rerun for this draft.

PHP 8.6 source: `f23b4c222cebe33be93ee8ecaaa14390c5507a08`; exact executable SHA-256 `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6`. PHP 8.5 comparator is the recorded 8.5.10 distribution, not an asserted exact-pin build. Original commands, raw diagnostics and binary/configuration identity are retained in [observations](../exact-pin-blocker-reconciliation/probes-verified.json).

## Minimal reproducer and adjacent controls

### `assign-write`

```php
<?php
($a=[1])[0]=2; echo json_encode($a);
```

Fixture `tests/fixtures/php/8.6/reconciliation/assign-write.php`; SHA-256 `3c8d6a370e48cbf661fdb9cde3d120854c38c0620af5e1ef64d73cb300ebc03f`.

- PHP 8.5 distribution: lint exit 255; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 0; runtime exit 255; stdout "\nFatal error: Invalid opcode 23/2/1. in C:\\Data\\GitHub\\php-grammar\\tests\\fixtures\\php\\8.6\\reconciliation\\assign-write.php on line 2\nStack trace:\n#0 {main}\n".
- PHP 8.6 legacy RC4 distribution: lint exit 0; runtime exit 255; stdout "\nFatal error: Invalid opcode 23/2/1. in <repository>\\tests\\fixtures\\php\\8.6\\reconciliation\\assign-write.php on line 2\nStack trace:\n#0 {main}\n". Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

### `reference-write`

```php
<?php
$b=[1]; ($a=&$b)[0]=2; echo json_encode([$a,$b]);
```

Fixture `tests/fixtures/php/8.6/reconciliation/reference-write.php`; SHA-256 `2f1b548b4df329bb8f00a39b4a8a96afb27a39d42d64dc8e26b91dcde0010acd`.

- PHP 8.5 distribution: lint exit 255; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 0; runtime exit 0; stdout "[[2],[2]]".
- PHP 8.6 legacy RC4 distribution: lint exit 0; runtime exit 0; stdout "[[2],[2]]". Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

### `list-variable-write`

```php
<?php
$b=[1]; ([$a]=$b)[0]=2; echo json_encode([$a,$b]);
```

Fixture `tests/fixtures/php/8.6/reconciliation/list-variable-write.php`; SHA-256 `32d20224fc58bb63abc4b99efb483ffe67ce08830dff6f9b2c41f6a6428576c7`.

- PHP 8.5 distribution: lint exit 255; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 0; runtime exit 255; stdout "\nFatal error: Invalid opcode 23/2/1. in C:\\Data\\GitHub\\php-grammar\\tests\\fixtures\\php\\8.6\\reconciliation\\list-variable-write.php on line 2\nStack trace:\n#0 {main}\n".
- PHP 8.6 legacy RC4 distribution: lint exit 0; runtime exit 255; stdout "\nFatal error: Invalid opcode 23/2/1. in <repository>\\tests\\fixtures\\php\\8.6\\reconciliation\\list-variable-write.php on line 2\nStack trace:\n#0 {main}\n". Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

### `list-literal-write`

```php
<?php
([$a]=[1])[0]=2; echo json_encode($a);
```

Fixture `tests/fixtures/php/8.6/reconciliation/list-literal-write.php`; SHA-256 `e665c782d1cc8a54054de4034d22d0f99f57ba1b12b4e24630ed0cb3898216ac`.

- PHP 8.5 distribution: lint exit 255; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 0; runtime exit 255; stdout "\nFatal error: Invalid opcode 23/1/1. in C:\\Data\\GitHub\\php-grammar\\tests\\fixtures\\php\\8.6\\reconciliation\\list-literal-write.php on line 2\nStack trace:\n#0 {main}\n".
- PHP 8.6 legacy RC4 distribution: lint exit 0; runtime exit 255; stdout "\nFatal error: Invalid opcode 23/1/1. in <repository>\\tests\\fixtures\\php\\8.6\\reconciliation\\list-literal-write.php on line 2\nStack trace:\n#0 {main}\n". Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

## Evidence and question

The ordinary write is structurally accepted by the repository and lints under exact RC4, then fails with Invalid opcode 23/2/1. Its ASSIGN produces T, consumed by ASSIGN_DIM. The reference control supplies V and produces [[2],[2]]. PHP 8.5 lint rejects both. Success of the reference control does not decide the ordinary form. No target restriction or permission can yet be added to the grammar.

Relevant upstream discussion: https://github.com/php/php-src/pull/20628

Pinned source: Zend/zend_compile.c:12774 (zend_compile_var_inner); Zend/zend_vm_def.h:2680 (ASSIGN_DIM VAR/CV handler). Adjacent gh20628_003.phpt and gh20628_005.phpt establish reads, not blanket write permission.

**Requested clarification:** Are ordinary assignment, reference assignment and list-derived dimension/property targets intended to be writable? For each operation/result-kind form, should compilation reject the source or emit a supported writable operand? What mutation/aliasing semantics distinguish ordinary and reference results?

Expected behavior is deliberately open pending an authoritative intended-validity decision. A source change, if required, must be tied to an immutable reviewed successor and discriminating regression tests before any grammar/specification action. The retained 26 writable controls / 31 traversal controls / five paired hook controls supply the broader comparison; counts are distinct, not one combined certification matrix.

The latest retained bounded PR refresh is Phase 7C.6; this draft makes no fresh upstream-search or absence claim. Reproduction commands are preserved per observation in [the draft evidence index](upstream-draft-evidence.json). Lint combines parsing and compilation. The separate non-executing opcode profile enables CLI OPcache dumps with optimization level zero and JIT disabled; it is not optimized-runtime evidence.
