<?php

declare(strict_types=1);

namespace TTM\Telemetry\Collector;

use TTM\Telemetry\TracerInterface;

final class TelemetryCollector
{
    private bool $active = false;

    public function __construct(
        /**
         * @var CollectorInterface[]
         */
        private readonly array $collectors,
        private readonly TracerInterface $tracer,
    ) {
        register_shutdown_function([$this, 'shutdown']);
    }

    public function startup(): void
    {
        $this->active = true;

        foreach ($this->collectors as $collector) {
            $collector->startup();
        }
    }

    public function shutdown(): void
    {
        if (!$this->active) {
            return;
        }

        foreach ($this->collectors as $collector) {
            $collector->shutdown();
        }

        $this->tracer->endAllSpans();
        $this->tracer->getContext()->resetContext();
        $this->active = false;
    }
}
