# PHP 8.6 stable structural release sign-off

**SIGNED OFF — STABLE STRUCTURAL GRAMMAR RELEASE READY**

Proposed final tag: `v1.1.0`. Official PHP tag: `php-8.6.0`; source commit: `8aaada3f9605d81a38b8240f93c7389dfbc77ef9`.

The independently reconstructed `php86-structural-v1` evidence passed all 27 mandatory gates.

Claim: Standalone source-backed PHP 8.6.0 structural grammar in the documented byte-source profile; contextual/compiler/AST/folding/runtime equivalence is not certified.

Evidence: `docs/8.6/structural-certification/runs/release-v1_1_0-r1/evidence.json`

SHA-256: `99bdd3a58d4962fe6d101576a156dad4300a38aaadd9c416659223166247a45f`.

- `grammar/8.6/php.ebnf`: `29f07eb51057e4878c90a7e2692f4e4b0a02ea03ab5cf5169e60e46226679b79`
- `grammar/8.6/php.md`: `9027e2971f051521c7d33ede7f1657c4b81c8ecc538d194b962188f3619156e6`
- `.audit/phase8b/source/x64/Release/php.exe`: `880d0073cb54474a886e2dd4d9e4088374c0e945920490ad60b7809b8d9b804b`

Source correspondence is reconstructed from the authenticated GA parser/scanner ledger and source locks; the exact source-built executable and DLL pass provenance verification. RC4, RC7 and PHP 8.5 profiles remain intact.

Seven productions differ from PHP 8.5: the five PHP86-001 PFA productions plus hook-name and yield-key. No production was added or removed. The latter two are documented historical representation corrections, not new language features.

Ordinary fixture coverage: 298/361 productions; 547/803 alternatives. Remaining entries have explicit primitive/trivia, scanner-lookahead, or separately executed focused-witness classifications. Coverage is not a claim of unique derivation or AST equivalence.

Full PHPUnit totals: `{"tests":6548,"assertions":59934}`. Hook-stage controls: 90. Focused structural witnesses: 124. Both short-tag profiles and paired PHP 8.5/8.6 boundaries passed. Scanner evidence covers the declared byte-source profile; it does not claim exhaustive scanner-state or multibyte equivalence.

PHP 8.5 grammar, Markdown, fixtures and historical evidence retain their baseline bytes. Fresh current compatibility gates passed, including public API/CLI and shared tooling. The unavailable historical manifest is not reconstructed or authenticated; current compatibility is not historical certification renewal.

Extended certification remains unresolved: PHP86-013, PHP86-014, PHP86-015, CA86-001, C6-CONTEXT, C6-COVERAGE and C6-PROVENANCE. The retained 38-obligation run has 26 passed, 3 failed and 9 blocked. Structural sign-off closes none of these extended obligations and implies no PHP-project endorsement.

Distribution archive: `docs/8.6/structural-certification/runs/release-v1_1_0-r1/package.tar`; SHA-256 `b5518534d39b424fd82fc25ec283ee642ba5beaeff83ed4ec3a29a5ae0629fc7`; 5914 individually hashed files. Full inventory: `docs/8.6/structural-certification/runs/release-v1_1_0-r1/package-integrity.json`. Temporary builds, caches, Git internals and vendor dependencies are excluded. Evidence and this deterministically reconstructed sign-off accompany the archive separately.

Final inputs are frozen in the evidence inventory. Existing maintainer-command defaults remain PHP 8.5; explicit 8.6 selection and latestVersion() are verified. No tag push, GitHub release publication or Packagist deployment is authorized by this sign-off.

Verify this derived sign-off with:

```text
php tools/8.6/structural-release.php --check-signoff --report docs/8.6/structural-certification/runs/release-v1_1_0-r1/evidence.json
```
