<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/app'])
    ->withSets([LevelSetList::UP_TO_PHP_83]);
