<?php

declare(strict_types=1);

namespace TTM\Telemetry\Collector\Collector;

/**
 * Trace data collector responsibility is to collect data during application lifecycle.
 */
interface CollectorInterface
{
    /**
     * @return string Collector's name.
     */
    public function getName(): string;

    /**
     * Called once at application startup.
     * Any initialization could be done here.
     */
    public function startup(): void;

    /**
     * Called once at application shutdown.
     * Cleanup could be done here.
     */
    public function shutdown(): void;
}
