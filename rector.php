<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    // 1) Tell Rector where your package code lives
    ->withPaths([
        __DIR__.'/src',
    ])
    // 3) Target PHP 8.4 features
    ->withPhpVersion(PhpVersion::PHP_84)
    // Also include Rector’s PHP upgrade rule sets up to your configured PHP version
    ->withPhpSets(php84: true)

    // 4) Opt into a sensible preset of quality and safety rules
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        naming: true,
        privatization: true,
        typeDeclarations: true,
        rectorPreset: true,
    )

    // 5) Laravel specific sets
    ->withSets([
        \RectorLaravel\Set\LaravelLevelSetList::UP_TO_LARAVEL_120,
    ])

    // 6) Import class names into use statements when it improves readability
    ->withImportNames(
        removeUnusedImports: true
    )
    // 7) Paths and files to skip
    ->withSkip([
        __DIR__.'/vendor',
        // Often you want to keep tests expressive and free from aggressive refactors
        // Comment this out if you want tests refactored too.
        __DIR__.'/tests/*',
        // Example: ignore generated files
        __DIR__.'/storage/*',
    ])

    // 8) Faster runs in CI and on larger packages
    ->withParallel();
