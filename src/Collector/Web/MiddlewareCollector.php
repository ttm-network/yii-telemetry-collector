<?php

declare(strict_types=1);

namespace TTM\Telemetry\Collector\Collector\Web;

use ReflectionClass;
use TTM\Telemetry\Collector\Collector\CollectorTrait;
use TTM\Telemetry\SpanInterface;
use TTM\Telemetry\TracerInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;

final class MiddlewareCollector
{
    use CollectorTrait;

    private array $stack = [];

    public function __construct(private readonly TracerInterface $tracer)
    {
    }

    public function collect(BeforeMiddleware|AfterMiddleware $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        $name = $this->getMiddlewareName($event);

        if ($event instanceof BeforeMiddleware) {
            $span = $this->tracer->startSpan(
                name: sprintf('middleware (%s)', $name),
            );
            $this->stack[] = [$span, ['memory' => memory_get_usage()]];
        } else {
            /** @var SpanInterface $span */
            [$span, $info] = array_pop($this->stack);
            $span->setAttribute('php.memory_usage', memory_get_usage() - $info['memory']);

            $this->tracer->endSpan($span);
        }
    }

    private function reset(): void
    {
        $this->stack = [];
    }

    private function getMiddlewareName(BeforeMiddleware|AfterMiddleware $event): string
    {
        $middleware = $event->getMiddleware();
        if (method_exists($middleware, '__debugInfo') && (new ReflectionClass($middleware))->isAnonymous()) {
            $callback = $middleware->__debugInfo()['callback'];
            return is_array($callback)
                ? implode('::', $callback)
                : 'object(Closure)#' . spl_object_id($callback);
        }

        return $middleware::class;
    }
}
