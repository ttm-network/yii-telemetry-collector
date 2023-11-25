<?php

declare(strict_types=1);

use Yiisoft\Telemetry\Collector\Collector\MiddlewareCollector;
use Yiisoft\Yii\Debug\Collector\Web\RequestCollector;
use Yiisoft\Yii\Debug\Collector\Web\WebAppInfoCollector;

return [
    'yiisoft/yii-telemetry-collector' => [
        'enabled' => true,
        'collectors' => [],
        'collectors.web' => [
            WebAppInfoCollector::class,
            RequestCollector::class,
            MiddlewareCollector::class,
        ],
        'collectors.console' => [],
    ]
];
