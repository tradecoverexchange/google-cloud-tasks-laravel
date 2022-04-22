<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job as BaseJob;

class Job extends BaseJob implements JobContract
{
    /**
     * @var string
     */
    public string $job;
    /**
     * @var array
     */
    public array $decoded;
    /**
     * @var Queue
     */
    public Queue $googleTasks;

    public function __construct(
        string $connectionName,
        Container $container,
        Queue $googleTasks,
        string $queue,
        protected CloudTask $task
    ) {
        $this->connectionName = $connectionName;
        $this->queue = $queue;
        $this->container = $container;
        $this->googleTasks = $googleTasks;

        $this->decoded = $this->payload();
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string
    {
        return $this->decoded['id'] ?? '';
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->task->payload();
    }

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);
        $job = unserialize($this->decoded['data']['command']);
        $this->googleTasks->release($this, $job, $delay);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return ($this->decoded['attempts'] ?? null) + 1;
    }
}
