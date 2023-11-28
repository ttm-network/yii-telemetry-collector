<?php

declare(strict_types=1);


use TTM\Telemetry\Collector\Collector\Web\MiddlewareCollector;
use TTM\Telemetry\Collector\Collector\Web\RequestCollector;

return [
    'yiisoft/yii-telemetry-collector' => [
        'enabled' => true,
        'collectors' => [],
        'collectors.web' => [
            RequestCollector::class,
            MiddlewareCollector::class,
        ],
        'collectors.console' => [],
    ]
];
