<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Api',
        __DIR__ . '/Console',
        __DIR__ . '/Controller',
        __DIR__ . '/Cron',
        __DIR__ . '/Gateway',
        __DIR__ . '/Helper',
        __DIR__ . '/Logger',
        __DIR__ . '/MessageQueue',
        __DIR__ . '/Model',
        __DIR__ . '/Observer',
        __DIR__ . '/Setup',
        //        __DIR__ . '/Test',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::DEAD_CODE
    ]);
};
