<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

interface Dispatcher
{
    public function dispatch(
        string $name,
        string $connection,
        string $payload,
        ?int $scheduledAt = null,
        string $queue = 'default'
    ): void;
}
