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

    private array $spans = [];

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

        $middlewareId = spl_object_id($event->getMiddleware());

        if ($event instanceof BeforeMiddleware) {
            $this->spans[$middlewareId] = $this->tracer->startSpan(
                name: sprintf('middleware (%s)', $name)
            );
        } else {
            $this->tracer->endSpan($this->spans[$middlewareId]);
        }
    }

    private function reset(): void
    {
        $this->spans = [];
    }
}
