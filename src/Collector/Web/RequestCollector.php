<?php

declare(strict_types=1);

namespace TTM\Telemetry\Collector\Collector\Web;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TTM\Telemetry\Collector\Collector\CollectorTrait;
use TTM\Telemetry\SpanInterface;
use TTM\Telemetry\TracerInterface;
use Yiisoft\Yii\Http\Event\AfterRequest;
use Yiisoft\Yii\Http\Event\BeforeRequest;

use function is_object;

final class RequestCollector
{
    use CollectorTrait;

    private ?SpanInterface $activeSpan = null;
    private ?ServerRequestInterface $request = null;

    public function __construct(
        private readonly TracerInterface $tracer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function collect(object $event): void
    {
        if (!is_object($event) || !$this->isActive()) {
            return;
        }

        if ($event instanceof BeforeRequest) {
            $request = $event->getRequest();

            $this->request = $request;
            $this->activeSpan = $this->tracer->startSpan(
                sprintf('%s %s', $request->getMethod(), $request->getUri()->getPath()),
                attributes: [
                    'url' => (string)$request->getUri(),
                    'query' => $request->getUri()->getQuery(),
                    'isAjax' => strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest',
                    'userIp' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                ]
            );
        } elseif ($event instanceof AfterRequest) {
            if (!$this->activeSpan) {
                $this->logger->warning('Failed to close span for request. No active spans.');
                return;
            }

            $this->tracer->endSpan($this->activeSpan);
        }
    }

    private function reset(): void
    {
        $this->activeSpan = null;
    }
}
