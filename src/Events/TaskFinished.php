<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Events;

use Illuminate\Http\Response;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTask;

class TaskFinished
{
    public function __construct(public CloudTask $task, public string|null $result, public Response $response)
    {
    }
}
