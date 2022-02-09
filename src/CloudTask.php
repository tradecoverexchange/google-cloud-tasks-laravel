<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Carbon\CarbonInterface;

interface CloudTask
{
    public function queueName(): string;

    public function taskName(): string;

    public function retryCount(): int;

    public function executionCount(): int;

    public function eta(): CarbonInterface;

    public function previousResponseStatusCode(): ?int;

    public function retryReason(): string;

    public function payload(): string;
}
