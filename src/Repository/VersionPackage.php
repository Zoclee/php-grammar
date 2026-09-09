<?php

declare(strict_types=1);

namespace PhpGrammar\Repository;

final readonly class VersionPackage
{
    public function __construct(
        public string $version,
        public string $rootProduction,
        public string $grammarPath,
        public string $documentationPath,
        public string $conformanceFixturePath,
        public string $lexerVersion,
    ) {
    }
}
