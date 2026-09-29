# Using php-grammar

The project is not yet published as a Composer package. Use a local checkout
to access the [canonical EBNF](../grammar/8.5/php.ebnf) and
[matching specification](../grammar/8.5/php.md) directly.

To use the PHP APIs and command-line tools, run this command from the checkout
root to install dependencies and generate the local autoloader:

```sh
composer install
```

Composer is used here to set up the checkout, not to download a published
php-grammar package. Runtime use requires PHP 8.2 or later; it does not require
a PHP 8.5 executable, ext-ast, or an upstream source cache.

## Discover, load, and inspect

In the example below, set `$root` to the absolute path of your local checkout.

```php
use PhpGrammar\Repository\RepositoryManifest;
use PhpGrammar\Php\Conformance\GrammarRepository;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;

$root = '/absolute/path/to/php-grammar';
require $root . '/vendor/autoload.php';

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

The specification is
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

Run these commands from the checkout root:

```sh
php bin/php-grammar versions
php bin/php-grammar rules 8.5
php bin/php-grammar rule 8.5 expression
php bin/php-grammar refs 8.5 variable-expression
php bin/php-grammar sections 8.5
php bin/php-grammar section 8.5 expressions
```

Outputs are deterministic JSON
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

Production identifiers are compatibility-sensitive. Pin a repository revision and
explicit PHP versions, tolerate additive manifest fields, and follow the
[alias migration and compatibility policy](api.md#compatibility-and-migration).
