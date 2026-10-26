# Should assignment-result dimension and property writes compile or be rejected?

Prepared October 26, 2026. **Unpublished; ready for user review.** This is a
language-validity question, not a proposed grammar correction or a claim of
official specification authority. Expected language behavior remains undecided.

## Affected versions and reproduction profile

Observed with PHP **8.5.10** (Windows distribution comparator, not an exact-source
attestation) and **8.6.0RC4 NTS VC2022 x64**, built from immutable PHP source
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`. The exact RC4 executable SHA-256 is
`445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6`;
core DLL SHA-256 is
`1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f`.
The 8.5 executable SHA-256 is
`32da1562c56dfe511a621627e3bbd9721058e855a6f14225c5803bc19ba623e9`.

The examples below are byte-identical retained fixtures, independently checked
against the authenticated Phase 7C.4 observations; they were not rerun in this
documentation phase. Save each fenced example separately. From the repository
root, use either executable with the recorded lint profile (PowerShell):

```powershell
& .audit/php85/php.exe -n -d zend.multibyte=0 -d short_open_tag=1 -d error_reporting=-1 -d memory_limit=64M -d max_execution_time=5 -d opcache.enable_cli=0 -d opcache.jit=0 -l example.php
& .audit/php86-exact/source/x64/Release/php.exe -n -d zend.multibyte=0 -d short_open_tag=1 -d error_reporting=-1 -d memory_limit=64M -d max_execution_time=5 -d opcache.enable_cli=0 -d opcache.jit=0 -l example.php
```

`example.php` is the selected fixture or saved example. Lint exit 0 means
compilation succeeded, with `No syntax errors detected in <file>`. Exit 255
means rejection; it is not by itself a pure-parser verdict. Exact original
command arrays, fixture hashes and full stdout/stderr are in the
[evidence index](reproducers.json), pointing into the unchanged
[full observations](../exact-pin-blocker-reconciliation/probes-verified.json).
CLI OPcache and JIT are disabled for lint; compiler built-in lowering still runs.
## Minimal reproducer and controls

### `assign-write`

```php
<?php
($a=[1])[0]=2; echo json_encode($a);
```

- PHP 8.5: lint exit 255; no runtime execution recorded.
- PHP 8.6: lint exit 0; runtime exit 255.

### `assign-read`

```php
<?php
$r=($a=[1])[0]; echo json_encode([$a,$r]);
```

- PHP 8.5: lint exit 0; runtime exit 0; stdout `[[1],1]`.
- PHP 8.6: lint exit 0; runtime exit 0; stdout `[[1],1]`.

### `reference-write`

```php
<?php
$b=[1]; ($a=&$b)[0]=2; echo json_encode([$a,$b]);
```

- PHP 8.5: lint exit 255; no runtime execution recorded.
- PHP 8.6: lint exit 0; runtime exit 0; stdout `[[2],[2]]`.

### `literal-write`

```php
<?php
[1][0]=2;
```

- PHP 8.5: lint exit 255; no runtime execution recorded.
- PHP 8.6: lint exit 255; no runtime execution recorded.

PHP 8.5 rejects the assignment-result write and reference-result write with
`Fatal error: Cannot use temporary expression in write context`. The literal
negative control has that diagnostic in both versions. RC4 compiles the ordinary
assignment-result write, then fails at runtime with
`Fatal error: Invalid opcode 23/2/1.` (line 2, followed by a main stack frame).
The echo merely exposes mutation in successful controls; it is not reached in
the failing case. To reproduce **only these controlled writable examples** at
runtime, use the same command above with `-l` omitted. Do not interpret a runtime
invalid opcode as parser rejection.

Additional small operation witnesses below are single PHP source lines after
`<?php`; their full fixture bytes and commands are indexed alongside the primary
examples. All lint-reject in 8.5 and lint-accept in exact RC4. A successful
reference control does not establish permission for ordinary assignment results.

| Operation / retained source | RC4 execution |
|---|---|| `($a=new stdClass)->p=2; echo json_encode($a);` | exit 255: `Invalid opcode 24/2/1.` |
| `$b=new stdClass; ($a=&$b)->p=2; echo json_encode([$a,$b]);` | exit 0: `[{"p":2},{"p":2}]` |
| `$b=[1]; ([$a]=$b)[0]=2; echo json_encode([$a,$b]);` | exit 255: `Invalid opcode 23/2/1.` |
| `([$a]=[1])[0]=2; echo json_encode($a);` | exit 255: `Invalid opcode 23/1/1.` |
| `$b=[1]; ([&$a]=$b)[0]=2; echo json_encode([$a,$b]);` | exit 0: `[2,[2]]` |
| `($a=[1])[0]+=2; echo json_encode($a);` | exit 255: `Invalid opcode 27/2/1.` |
| `unset(($a=[1])[0]); echo json_encode($a);` | exit 255: `Invalid opcode 75/2/1.` |
| `$r=&($a=[1])[0]; $r=2; echo json_encode([$a,$r]);` | exit 255: `Invalid opcode 84/2/1.` |

## Implementation and precise question

Pinned [`zend_compile_var_inner`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L12774) permits assignment ASTs; ordinary assignment produces TMP, reference assignment VAR, and the literal-list case can supply CONST. [`ASSIGN_DIM`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_vm_def.h#L2680) has VAR/CV write handlers. These facts explain the failing operand path, but do not decide the intended accepted language. The pinned [read regression](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/tests/gh20628_003.phpt) and [related regression](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/tests/gh20628_005.phpt) do not grant blanket write permission.

For ordinary assignment, reference assignment and destructuring results, should
dimension/property assignment, compound assignment, unset and reference binding
be rejected during compilation, or supported through valid writable operands?
If supported, what mutation and aliasing behavior is intended for each result
kind? Is the ordinary/list failure an emission defect, a missing compile-time
rejection, or a mixed boundary depending on the exact operation?

Proposed location: a focused php-src issue referencing
[PR 20628](https://github.com/php/php-src/pull/20628), unless maintainers identify
an existing exact-form issue. The refreshed PR comments discuss a separate
by-reference argument change and assertion repair; their intent statements do
not answer these target forms. [PR 20903](https://github.com/php/php-src/pull/20903)
materializes writable constant-object operands under a different feature; it
does not settle assignment-derived containers.

No EBNF restriction is proposed to reproduce a VM failure. Confirmation of
rejection would first require coherent compiler behavior and a scoped contextual
rule; confirmation of support requires valid operands and regression evidence.
Either answer requires a separate Gate B review before normative changes.

## Review boundary

The [October 26 upstream review](upstream-review.md) distinguishes unchanged
PR evidence from newly retrieved related material. No authoritative answer to
this exact question was found within that scope. The [response/action map](response-actions.json)
retains the original work item and separates future EBNF, contextual, fixture
and upstream actions. An unanswered or qualified response leaves the blocker
open. No issue has been posted and no maintainer has been contacted.
