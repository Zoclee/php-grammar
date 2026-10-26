# Phase 7F final validation results

Current assessment: **29 passed / 0 failed / 9 blocked** across 36 gates and two additive prerequisites. This is obligation accounting, not a test count. All seven original blockers remain.

Full PHPUnit suite: **6292 tests, 58829 assertions**. Compatibility: **15 commands**, **4164 dependencies**. See [summary.json](summary.json) and [validation.json](validation.json) for exact commands and exit codes.

Preservation: **4323 baseline files** byte-identical. **82 phase files** hashed; **28 JSON files** parsed; **17 report links** checked. No certification or promotion is granted.

The changed-input differential checkpoint is retained separately from the fresh final report. Historical failures remain in the base run; they are not rewritten as passes.

Scanner token-boundary comparison: **8 equal normalized sequences, 4 different retained arrays, 2 native lexer rejections**. The four differences are the newline-terminated shebang and the adapter's atomic interpolated-string/heredoc/nowdoc tokens. Exact PHP exposes their component tokens. These representation differences are retained in [scanner-comparisons.json](scanner-comparisons.json); they do not establish full scanner or contextual equivalence.

Fresh raw legacy 36-gate totals: `{"passed":21,"failed":5,"blocked":10}`. Separate PHP 8.5 release totals: `{"PASS":25,"FAIL":2}`. These original outcomes remain preserved alongside the obligation-preserving current assessment; current compatibility does not renew historical certification.
