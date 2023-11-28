<?php

declare(strict_types=1);

use TTM\Telemetry\Collector\Collector\Web\MiddlewareCollector;
use TTM\Telemetry\Collector\Collector\Web\WebAppInfoCollector;
use TTM\Telemetry\Collector\TelemetryCollector;
use Yiisoft\ErrorHandler\Event\ApplicationError;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use Yiisoft\Yii\Http\Event\AfterEmit;
use Yiisoft\Yii\Http\Event\AfterRequest;
use Yiisoft\Yii\Http\Event\ApplicationShutdown;
use Yiisoft\Yii\Http\Event\ApplicationStartup;
use Yiisoft\Yii\Http\Event\BeforeRequest;

if (!(bool)($params['yiisoft/yii-telemetry-collector']['enabled'] ?? false)) {
    return [];
}

return [
    ApplicationStartup::class => [
        [TelemetryCollector::class, 'startup'],
        [WebAppInfoCollector::class, 'collect'],
    ],
    ApplicationShutdown::class => [
        [WebAppInfoCollector::class, 'collect'],
        [TelemetryCollector::class, 'shutdown'],
    ],
    BeforeRequest::class => [
        [WebAppInfoCollector::class, 'collect'],
    ],
    AfterRequest::class => [
        [WebAppInfoCollector::class, 'collect'],
    ],
    AfterEmit::class => [
        [WebAppInfoCollector::class, 'collect'],
    ],
    BeforeMiddleware::class => [
        [MiddlewareCollector::class, 'collect'],
    ],
    AfterMiddleware::class => [
        [MiddlewareCollector::class, 'collect'],
    ],
    ApplicationError::class => [
        [WebAppInfoCollector::class, 'collect'],
    ],
];
