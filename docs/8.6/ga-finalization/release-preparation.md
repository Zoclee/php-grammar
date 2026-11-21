# Conditional php-grammar release preparation

November 20, 2026. **Blocked; not approved publication copy.**

The configured project remote has tags v1.0.0, v1.0.1 and v1.0.2. The latest
advertised tag is v1.0.2 at `7d8b6e5fa92f82e7e8c122a9a989a22727f61c3e`.
See [remote tag evidence](baseline-remote-tags.log). The
[GitHub API](project-release.body) confirms v1.0.2 is the latest published
non-prerelease release (October 6, 2026). The
[Packagist query](packagist.receipt.json) returned HTTP 404; no public Packagist
release is asserted. Local tags were absent.
The prospective next minor version is **v1.1.0**, conditional on completing the
backward-compatible PHP 8.6 addition. This is the repository package version;
the authenticated language release is **PHP 8.6.0**.

Proposed eventual release title: **v1.1.0 — PHP 8.6 grammar support**.
That title must not be published while PHP 8.6 remains an uncertified draft.
A final description must name the actual certified bounded claim, standalone
artifacts, source pin, interpreter provenance, validation results, limitations,
installation instructions and unchanged PHP 8.5 selection. It must not imply
official PHP-project endorsement or a complete compiler validator.

The package's existing installation and explicit-version APIs remain unchanged.
Consumers should continue following the existing README/API guidance and using
PHP 8.5 for the recommended stable grammar. Selecting 8.6 still selects a draft;
no numerical-latest rule is substituted for the documented default policy.

The canonical prospective inventory remains `grammar/8.6/php.ebnf`, `php.md`,
`changes.md`, manifest metadata and the existing consumer library/CLI. The new
GA evidence is an additive blocked-release record. It is not a finalized package
inventory or a successful release archive. Ignored `.audit` source trees,
SDKs, binaries, temporary files, `vendor` and PHPUnit caches must not enter a
published package. Existing Composer archive exclusions remain intact.

Before publication, complete every remediation in
[final-certification.md](../final-certification.md), adopt and independently
verify the GA evidence contract, finalize README/API/manifest/change/release
notes, verify the actual archive and freeze a reviewed release commit. Then
rerun the full applicable release gates and freshness checks against that commit.
Only successful sign-off permits preparation of an annotated tag and exact
publication commands for maintainer approval.

No stable tag, release archive, final GitHub description, push or publication is
approved or produced in this blocked run. Historical PHP 8.5 release metadata
and contracts remain untouched.
