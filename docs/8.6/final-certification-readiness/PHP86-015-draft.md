# Unpublished maintainer discussion draft: PHP86-015

Is optimized printf/sprintf traversal intended to change discarded-cast validity?

Prepared October 26, 2026 from retained Phase 7C.4 observations. Not submitted; no maintainer contacted. No experiment rerun for this draft.

PHP 8.6 source: `f23b4c222cebe33be93ee8ecaaa14390c5507a08`; exact executable SHA-256 `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6`. PHP 8.5 comparator is the recorded 8.5.10 distribution, not an asserted exact-pin build. Original commands, raw diagnostics and binary/configuration identity are retained in [observations](../exact-pin-blocker-reconciliation/probes-verified.json).

## Minimal reproducer and adjacent controls

### `printf-or`

```php
<?php
printf(true || (unset)$x);
```

Fixture `tests/fixtures/php/8.6/reconciliation/printf-or.php`; SHA-256 `8d456ffa6b7698e523ef122c4a54ed5cd2d6086c8befcea258f9b228f87c0b16`.

- PHP 8.5 distribution: lint exit 0; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 255; no runtime execution recorded.
- PHP 8.6 legacy RC4 distribution: lint exit 255; no runtime execution recorded. Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

### `dynamic-or`

```php
<?php
$f="printf"; $f(true || (unset)$x);
```

Fixture `tests/fixtures/php/8.6/reconciliation/dynamic-or.php`; SHA-256 `0c7646f695f8e0d149b4db71ae5c1046515da74b238ed067208c654ed7a3ac62`.

- PHP 8.5 distribution: lint exit 0; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 0; no runtime execution recorded.
- PHP 8.6 legacy RC4 distribution: lint exit 0; no runtime execution recorded. Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

### `printf-ternary`

```php
<?php
printf(false ? (unset)$x : "ok");
```

Fixture `tests/fixtures/php/8.6/reconciliation/printf-ternary.php`; SHA-256 `336bb062e228013a1d527237b9b85cb7f1cd06a5c263316a8ca75f0f04e05364`.

- PHP 8.5 distribution: lint exit 255; no runtime execution recorded.
- PHP 8.6 exact-source RC4: lint exit 0; no runtime execution recorded.
- PHP 8.6 legacy RC4 distribution: lint exit 0; no runtime execution recorded. Retained comparison from [Phase 5C](../compiler-contextual-probes.json), not an exact-pin attestation.

## Evidence and question

PHP 8.5 accepts printf(true || (unset)$x); exact RC4 rejects it. Both accept the dynamic equivalent. The ternary printf witness reverses the difference: PHP 8.5 rejects; exact RC4 accepts and emits ECHO "ok". These are lint/compilation observations with CLI OPcache disabled, not runtime results or pure parser verdicts. No unconditional argument-traversal or function-specific EBNF rule is justified.

Relevant upstream discussion: https://github.com/php/php-src/pull/19658

Pinned source: Zend/zend_compile.c: zend_compile_func_printf, zend_compile_func_sprintf and zend_eval_const_expr; exact numbered definition anchors are listed below. Also see the retained source-review.json slices for dispatch eligibility and short-circuit/conditional/coalesce compilation.

- `Zend/zend_compile.c:5096`: `static zend_result zend_compile_func_sprintf(znode *result, zend_ast_list *args) /* {{{ */`

- `Zend/zend_compile.c:5264`: `static zend_result zend_compile_func_printf(znode *result, zend_ast_list *args) /* {{{ */`

- `Zend/zend_compile.c:12920`: `static void zend_eval_const_expr(zend_ast **ast_ptr) /* {{{ */`

**Requested clarification:** Should a direct optimized builtin validate otherwise discarded ASTs differently from a dynamic/namespace/named/unpacked/PFA call? Are the logical-versus-ternary/coalesce differences intended source validity or an optimization artifact, and what exact initializer and dispatch boundaries should tools implement?

Expected behavior is deliberately open pending an authoritative intended-validity decision. A source change, if required, must be tied to an immutable reviewed successor and discriminating regression tests before any grammar/specification action. The retained 26 writable controls / 31 traversal controls / five paired hook controls supply the broader comparison; counts are distinct, not one combined certification matrix.

The latest retained bounded PR refresh is Phase 7C.6; this draft makes no fresh upstream-search or absence claim. Reproduction commands are preserved per observation in [the draft evidence index](upstream-draft-evidence.json). Lint combines parsing and compilation. The separate non-executing opcode profile enables CLI OPcache dumps with optimization level zero and JIT disabled; it is not optimized-runtime evidence.
