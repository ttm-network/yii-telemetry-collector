<?php

declare(strict_types=1);

namespace TTM\Telemetry\Collector\Collector\Web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TTM\Telemetry\Collector\Collector\CollectorTrait;
use TTM\Telemetry\SpanInterface;
use TTM\Telemetry\TracerInterface;
use Yiisoft\Yii\Http\Event\AfterEmit;
use Yiisoft\Yii\Http\Event\AfterRequest;
use Yiisoft\Yii\Http\Event\ApplicationShutdown;
use Yiisoft\Yii\Http\Event\ApplicationStartup;
use Yiisoft\Yii\Http\Event\BeforeRequest;

final class WebAppInfoCollector
{
    use CollectorTrait;

    private ?SpanInterface $activeSpan = null;
    private ?ServerRequestInterface $request = null;
    private ?ResponseInterface $response = null;

    public function __construct(
        private readonly TracerInterface $tracer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function collect(object $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        match (true) {
            $event instanceof ApplicationStartup => $this->handleApplicationStartup(),
            $event instanceof BeforeRequest => $this->request = $event->getRequest(),
            $event instanceof AfterRequest => $this->handleAfterRequest($event),
            $event instanceof AfterEmit => $this->activeSpan->addEvent('Data emitted'),
            $event instanceof ApplicationShutdown => $this->handleApplicationShutdown(),
        };
    }

    private function handleApplicationStartup(): void
    {
        $this->activeSpan = $this->tracer->startSpan(
            name: 'Start Application Process',
            scoped: true
        );
    }

    private function handleAfterRequest(AfterRequest $event): void
    {
        $this->activeSpan->addEvent('Request processing completed');
        $this->response = $event->getResponse();
    }

    private function handleApplicationShutdown(): void
    {
        $this->activeSpan->updateName(
            sprintf('%s %s', $this->request->getMethod(), $this->request->getUri()->getPath())
        );
        $this->activeSpan->setAttributes([
            'http.wrote_bytes' => $this->response->getBody()->getSize(),
            'http.user_ip' => $this->request->getServerParams()['REMOTE_ADDR'] ?? null,
            'http.user_agent' => $this->request->getHeaderLine('User-Agent'),
            'http.status_code' => $this->response->getStatusCode(),
            'http.scheme' => $this->request->getUri()->getScheme(),
            'http.route' => $this->request->getUri()->getPath(),
            'http.method' => $this->request->getMethod(),
            'http.flavor' => $this->response->getProtocolVersion()
        ]);
        $this->tracer->endSpan($this->activeSpan);
    }

    private function reset(): void
    {
        $this->activeSpan = null;
        $this->request = null;
        $this->response = null;
    }
}
