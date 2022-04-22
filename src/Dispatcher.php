<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;

interface Dispatcher
{
    public function dispatch(
        string $name,
        string $connection,
        string $payload,
        int|null $scheduledAt = null,
        string $queue = 'default'
    ): void;

    public function client(): CloudTasksClient;

    public function size($queue = null): int;
}
