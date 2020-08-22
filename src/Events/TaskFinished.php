<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Events;

use Illuminate\Http\Response;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTask;

class TaskFinished
{
    /**
     * @var CloudTask
     */
    public $task;
    /**
     * @var string|null
     */
    public $result;
    /**
     * @var Response
     */
    public $response;

    public function __construct(CloudTask $task, ?string $result, Response $response)
    {
        $this->task = $task;
        $this->result = $result;
        $this->response = $response;
    }
}
