<?php

declare(strict_types=1);


use TTM\Telemetry\Collector\Collector\Web\MiddlewareCollector;

return [
    'yiisoft/yii-telemetry-collector' => [
        'enabled' => true,
        'collectors' => [],
        'collectors.web' => [
            //WebAppInfoCollector::class,
            //RequestCollector::class,
            MiddlewareCollector::class,
        ],
        'collectors.console' => [],
    ]
];
