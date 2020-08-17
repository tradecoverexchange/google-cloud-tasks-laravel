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
    public $job;
    /**
     * @var array
     */
    public $decoded;
    /**
     * @var Queue
     */
    public $googleTasks;
    /**
     * @var CloudTask
     */
    private $task;

    public function __construct(
        string $connectionName,
        Container $container,
        Queue $googleTasks,
        string $queue,
        CloudTask $task
    ) {
        $this->connectionName = $connectionName;
        $this->queue = $queue;
        $this->container = $container;
        $this->googleTasks = $googleTasks;
        $this->task = $task;

        $this->decoded = $this->payload();
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->decoded['id'] ?? '';
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->task->payload();
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->task->executionCount();
    }
}
