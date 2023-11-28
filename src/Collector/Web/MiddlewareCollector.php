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
                scoped: true
            );
            $this->stack[] = [$span, ['memory' => memory_get_usage()]];
        } else {
            /** @var SpanInterface $span */
            [$span, $info] = array_pop($this->stack);
            $span->setAttribute(
                'memory_usage',
                $this->formatMemoryUsage(memory_get_usage() - $info['memory'])
            );
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

    private function formatMemoryUsage($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
