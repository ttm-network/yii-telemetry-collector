<?php

declare(strict_types=1);

use TTM\Telemetry\Collector\TelemetryCollector;
use Yiisoft\Definitions\ReferencesArray;

if (!(bool)($params['yiisoft/yii-telemetry-collector']['enabled'] ?? false)) {
    return [];
}

/** @var array $params */
return [
    TelemetryCollector::class => [
        'class' => TelemetryCollector::class,
        '__construct()' => [
            'collectors' => ReferencesArray::from(
                array_merge(
                    $params['yiisoft/yii-telemetry-collector']['collectors'],
                    $params['yiisoft/yii-telemetry-collector']['collectors.web'] ?? [],
                )
            ),
        ],
    ],
];
