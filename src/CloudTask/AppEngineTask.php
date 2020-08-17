<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\CloudTask;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTask;

class AppEngineTask implements CloudTask
{
    /**
     * @var Request
     */
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function queueName(): string
    {
        return (string) $this->request->header('X-AppEngine-QueueName', 'default');
    }

    public function taskName(): string
    {
        return (string) $this->request->header('X-AppEngine-TaskName', '');
    }

    public function retryCount(): int
    {
        return (int) $this->request->header('X-AppEngine-TaskRetryCount', '0');
    }

    public function executionCount(): int
    {
        return (int) $this->request->header('X-AppEngine-TaskExecutionCount', '0');
    }

    public function eta(): CarbonInterface
    {
        return Carbon::createFromTimestamp(
            (int) $this->request->header('X-AppEngine-TaskETA', '0')
        );
    }

    public function previousResponseStatusCode(): ?int
    {
        return $this->request->hasHeader('X-AppEngine-TaskPreviousResponse') ?
            (int) $this->request->header('X-AppEngine-TaskPreviousResponse') :
            null;
    }

    public function retryReason() : string
    {
        return (string) $this->request->header('X-AppEngine-TaskRetryReason');
    }

    public function payload() : string
    {
        return (string) $this->request->getContent();
    }
}
