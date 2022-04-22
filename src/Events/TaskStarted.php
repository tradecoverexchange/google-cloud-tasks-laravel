<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Events;

use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTask;

class TaskStarted
{
    public function __construct(public CloudTask $task)
    {
    }
}
