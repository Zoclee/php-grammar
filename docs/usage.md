# Using php-grammar

Install the Composer package from the package repository/release used by your
project, then load your application's `vendor/autoload.php`:

```sh
composer require php-grammar/php-grammar
```

If that package is not available in your configured Composer registry, add this
checkout as a Composer `path` repository (URL is the checkout's absolute path)
and require `php-grammar/php-grammar` at `@dev`. Pin a released package constraint
in production. For a source checkout, `composer install` supplies the local
autoload file and development tools. Runtime use requires PHP >=8.2, no PHP 8.5
executable, ext-ast or upstream source cache.

## Discover, load, and inspect

```php
require __DIR__ . '/vendor/autoload.php';

use Composer\InstalledVersions;
use PhpGrammar\Repository\RepositoryManifest;
use PhpGrammar\Php\Conformance\GrammarRepository;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;

$root = InstalledVersions::getInstallPath('php-grammar/php-grammar');
$manifest = RepositoryManifest::fromRepositoryRoot($root);
$versions = $manifest->versions();                   // ['8.5']
$latest = $manifest->latestVersion();                // highest advertised version
$package = $manifest->package('8.5');                // explicit reproducible selection
$specification = $manifest->absolutePath($package->documentationPath);
$repository = new GrammarRepository($manifest);
$grammar = $repository->load('8.5');
$names = $grammar->productionNames();                // 360 names, source order
$expression = $grammar->production('expression');    // Production, with expression tree
$direct = $expression->references();
$users = $grammar->referencesTo('expression');
```

In a checkout, `$root` may be the checkout directory instead of
`InstalledVersions::getInstallPath()`. The specification is
[`grammar/8.5/php.md`](../grammar/8.5/php.md). Read files using manifest paths;
do not infer support from a directory name or from the host PHP version.

## Match source or a selected production

```php
$matcher = PhpGrammarMatcher::forManifest($manifest)->withShortOpenTag(false);
$file = $matcher->matches('8.5', '<?php echo 1;');
$valid = $matcher->matchesRule('8.5', 'expression', '$a + 1');
$invalid = $matcher->matchesRule('8.5', 'expression', '$a +');
assert($file->matched && $valid->matched && !$invalid->matched);
```

Whole source includes its PHP tags; fragments are lexed in PHP-code mode.
Matching requires full structural consumption. Acceptance covers lexical/source
processing and structural grammar, but the third specification layer—contextual
compiler restrictions—is only partially implemented. It is not a complete
PHP-validity check. The matcher produces no AST. The host PHP runtime does not
select the grammar version.

## Primitives and semantic sections

```php
$primitive = $manifest->lexicalPrimitive('nowdoc-code-unit');
assert($primitive['independentMatcher'] === false);
$scannerResponsibility = $primitive['scannerContext'];
$index = $repository->productionIndex('8.5');
$sections = $index->sections();
$sectionId = $index->sectionForRule('expression');     // expressions
$expressionRules = $index->rulesInSection($sectionId);
```

Primitive meanings are byte-oriented and scanner-state dependent. Never replace
`html-code-unit`, `line-comment-code-unit`, `encapsed-code-unit`, or
`nowdoc-code-unit` with an unrestricted wildcard. The PHP matcher configures its
own lexer and token adapters. Raw EBNF consumers must implement the documented
lexical contract themselves. Section IDs are machine identifiers; titles may
change for readability.

## Handle errors

Use `hasVersion()` and `hasProduction()` for optional selections. Unknown versions
throw `RepositoryManifestException`; unknown `production()` or section lookups
throw `OutOfBoundsException`. Unknown `matchesRule()` selections preserve the
existing failed `MatchResult` behavior and explain the name/version in `expected`.
Malformed EBNF raises EBNF lexer/parser exceptions, while an unreadable or invalid
repository grammar raises `ConformanceException`. `expected` and `furthestOffset`
are debugging aids; offsets are token indices for normal PHP matching, not always
source bytes. See the [full API contract](api.md).

## Command line

```sh
vendor/bin/php-grammar versions
vendor/bin/php-grammar rules 8.5
vendor/bin/php-grammar rule 8.5 expression
vendor/bin/php-grammar refs 8.5 variable-expression
vendor/bin/php-grammar sections 8.5
vendor/bin/php-grammar section 8.5 expressions
```

In a checkout use `php bin/php-grammar` instead. Outputs are deterministic JSON
for the selected package contents. `refs` provides direct and reverse references.
To validate the package manifest as a maintainer:

```sh
php tools/validate-manifest.php
```

The executable consumer examples are in
[`ConsumerWorkflowTest.php`](../tests/Repository/ConsumerWorkflowTest.php) and
[`ConsumerCliTest.php`](../tests/Repository/ConsumerCliTest.php); run
`php vendor/phpunit/phpunit/phpunit --filter Consumer` in a development checkout.
They use the documented public interfaces without fixture-runner internals.

Production identifiers are compatibility-sensitive. Pin package releases and
explicit PHP versions, tolerate additive manifest fields, and follow the
[alias migration and compatibility policy](api.md#compatibility-and-migration).
