<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Controllers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Response;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use TradeCoverExchange\GoogleCloudTaskLaravel\Events;
use TradeCoverExchange\GoogleCloudTaskLaravel\GoogleCloudTasks;
use TradeCoverExchange\GoogleCloudTaskLaravel\TaskFactory;

class CloudTasksController
{
    /**
     * @var Worker
     */
    private $worker;

    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
    }

    public function __invoke(
        GoogleCloudTasks $cloudTasks,
        TaskFactory $factory,
        Dispatcher $dispatcher,
        string $connection
    ) : Response {
        $task = $factory->make($connection);

        $dispatcher->subscribe($cloudTasks);
        $dispatcher->dispatch(new Events\TaskStarted(
            $task
        ));

        $this->worker->runNextJob(
            $connection,
            $task->queueName(),
            new WorkerOptions(0, 128, 60, 3, 3)
        );

        $response = $cloudTasks->getResponse() ?? new Response(Response::HTTP_INTERNAL_SERVER_ERROR);

        $dispatcher->dispatch(new Events\TaskFinished(
            $task,
            $cloudTasks->getResult(),
            $response
        ));

        return $response;
    }
}
