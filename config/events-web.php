<?php

declare(strict_types=1);

use TTM\Telemetry\Collector\Collector\Web\MiddlewareCollector;
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
        //[WebAppInfoCollector::class, 'collect'],
    ],
    ApplicationShutdown::class => [
        //[WebAppInfoCollector::class, 'collect'],
    ],
    BeforeRequest::class => [
        [TelemetryCollector::class, 'startup'],
//        [WebAppInfoCollector::class, 'collect'],
//        [RequestCollector::class, 'collect'],
    ],
    AfterRequest::class => [
//        [WebAppInfoCollector::class, 'collect'],
//        [RequestCollector::class, 'collect'],
    ],
    AfterEmit::class => [
//        [ProfilerInterface::class, 'flush'],
//        [WebAppInfoCollector::class, 'collect'],
        [TelemetryCollector::class, 'shutdown'],
    ],
    BeforeMiddleware::class => [
        [MiddlewareCollector::class, 'collect'],
    ],
    AfterMiddleware::class => [
        [MiddlewareCollector::class, 'collect'],
    ],
    ApplicationError::class => [
        //[ExceptionCollector::class, 'collect'],
    ],
];
