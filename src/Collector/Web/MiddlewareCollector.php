<?php

declare(strict_types=1);

namespace TTM\Telemetry\Collector\Collector\Web;

use ReflectionClass;
use TTM\Telemetry\Collector\Collector\CollectorTrait;
use TTM\Telemetry\TracerInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;

final class MiddlewareCollector
{
    use CollectorTrait;

    private array $beforeStack = [];
    private array $afterStack = [];

    public function __construct(private readonly TracerInterface $tracer)
    {
    }

    public function collect(BeforeMiddleware|AfterMiddleware $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        if (
            method_exists($event->getMiddleware(), '__debugInfo')
            && (new ReflectionClass($event->getMiddleware()))->isAnonymous()
        ) {
            $callback = $event->getMiddleware()->__debugInfo()['callback'];
            if (is_array($callback)) {
                $name = implode('::', $callback);
            } else {
                $name = 'object(Closure)#' . spl_object_id($callback);
            }
        } else {
            $name = $event->getMiddleware()::class;
        }

        if ($event instanceof BeforeMiddleware) {
            $this->beforeStack[] = [
                'name' => $name,
                'time' => microtime(true),
                'memory' => memory_get_usage(),
                'request' => $event->getRequest(),
            ];
        } else {
            $this->afterStack[] = [
                'name' => $name,
                'time' => microtime(true),
                'memory' => memory_get_usage(),
                'response' => $event->getResponse(),
            ];
        }


        die();
    }

    private function reset(): void
    {
        $this->beforeStack = [];
        $this->afterStack = [];
    }

    private function getActionHandler(array $beforeAction, array $afterAction): array
    {
        return [
            'name' => $beforeAction['name'],
            'startTime' => $beforeAction['time'],
            'request' => $beforeAction['request'],
            'response' => $afterAction['response'],
            'endTime' => $afterAction['time'],
            'memory' => $afterAction['memory'],
        ];
    }
}
