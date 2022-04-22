<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tasks;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTask;

class HttpCloudTask implements CloudTask
{
    public function __construct(protected Request $request)
    {
    }

    public function queueName(): string
    {
        return (string) $this->request->header('X-CloudTasks-QueueName', 'default');
    }

    public function taskName(): string
    {
        return (string) $this->request->header('X-CloudTasks-TaskName', '');
    }

    public function retryCount(): int
    {
        return (int) $this->request->header('X-CloudTasks-TaskRetryCount', '0');
    }

    public function executionCount(): int
    {
        return (int) $this->request->header('X-CloudTasks-TaskExecutionCount', '0');
    }

    public function eta(): CarbonInterface
    {
        return Carbon::createFromTimestamp(
            (int) $this->request->header('X-CloudTasks-TaskETA', '0')
        );
    }

    public function previousResponseStatusCode(): int|null
    {
        return $this->request->hasHeader('X-CloudTasks-TaskPreviousResponse') ?
            (int) $this->request->header('X-CloudTasks-TaskPreviousResponse') :
            null;
    }

    public function retryReason(): string
    {
        return (string) $this->request->header('X-CloudTasks-TaskRetryReason');
    }

    public function payload(): string
    {
        return (string) $this->request->getContent();
    }
}
