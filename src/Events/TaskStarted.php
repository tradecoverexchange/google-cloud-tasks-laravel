<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Events;

use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTask;

class TaskStarted
{
    /**
     * @var CloudTask
     */
    public $task;

    public function __construct(CloudTask $task)
    {
        $this->task = $task;
    }
}
