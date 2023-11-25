<?php

declare(strict_types=1);

namespace TTM\Telemetry\Collector;

final class TelemetryCollector
{
    private bool $active = false;

    public function __construct(
        /**
         * @var CollectorInterface[]
         */
        private array $collectors
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
        $this->active = false;
    }
}
