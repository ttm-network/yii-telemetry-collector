<?php

declare(strict_types=1);

namespace TTM\Telemetry\Collector\Collector\Web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TTM\Telemetry\Collector\Collector\CollectorTrait;
use TTM\Telemetry\Context\ContextExtractorInterface;
use TTM\Telemetry\SpanInterface;
use TTM\Telemetry\TracerInterface;
use Yiisoft\ErrorHandler\Event\ApplicationError;
use Yiisoft\Yii\Http\Event\AfterEmit;
use Yiisoft\Yii\Http\Event\AfterRequest;
use Yiisoft\Yii\Http\Event\ApplicationShutdown;
use Yiisoft\Yii\Http\Event\BeforeRequest;

final class WebAppInfoCollector
{
    use CollectorTrait;

    private ?SpanInterface $activeSpan = null;
    private ?ServerRequestInterface $request = null;
    private ?ResponseInterface $response = null;

    public function __construct(
        private readonly TracerInterface $tracer,
        private readonly ContextExtractorInterface $contextExtractor,
        private readonly LoggerInterface $logger
    ) {
    }

    public function collect(object $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        match (true) {
            $event instanceof BeforeRequest => $this->handleBeforeRequest($event->getRequest()),
            $event instanceof AfterRequest => $this->handleAfterRequest($event),
            $event instanceof AfterEmit => $this->activeSpan->addEvent('Data emitted'),
            $event instanceof ApplicationError => $this->activeSpan->recordException($event->getThrowable()),
            $event instanceof ApplicationShutdown => $this->handleApplicationShutdown(),
            default => false
        };
    }

    private function handleBeforeRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
        $context = $this->contextExtractor->extract($request->getHeaders())->current();

        if ($context !== []) {
            $this->tracer->getContext()->setContext($context);
        }

        $this->activeSpan = $this->tracer->startSpan(name: __METHOD__, scoped: true);
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
            'php.memory_peak_usage' => memory_get_peak_usage(),
            'http.response_content_length' => $this->response->getBody()->getSize(),
            'http.user_ip' => $this->request->getServerParams()['REMOTE_ADDR'] ?? null,
            'http.user_agent' => $this->request->getHeaderLine('User-Agent'),
            'http.status_code' => $this->response->getStatusCode(),
            'http.scheme' => $this->request->getUri()->getScheme(),
            'http.route' => $this->request->getUri()->getPath(),
            'http.request_id' => $this->response->getHeaderLine('X-Request-Id'),
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
