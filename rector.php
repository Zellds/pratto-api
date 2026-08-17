<?php

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/app'])
    ->withSets([LevelSetList::UP_TO_PHP_83])
    // deptrac-shim 1.0.2 bundles a php-parser version that cannot parse PHP 8.3
    // typed class constants; skip this rule on the two files that hit it so
    // deptrac keeps analysing them instead of silently failing to parse them.
    ->withSkip([
        AddTypeToConstRector::class => [
            __DIR__.'/app/Infrastructure/Media/ImagePipeline.php',
            __DIR__.'/app/Infrastructure/Media/TemporaryMediaUrlSigner.php',
        ],
    ]);
