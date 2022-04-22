<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Controllers;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use TradeCoverExchange\GoogleCloudTaskLaravel\Events;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\TaskFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\GoogleCloudTasks;

class CloudTasksController
{
    public function __construct(protected Worker $worker)
    {
    }

    public function __invoke(
        Request $request,
        GoogleCloudTasks $cloudTasks,
        TaskFactory $factory,
        Dispatcher $dispatcher,
        Repository $cache,
        string $connection,
        string $queue,
    ): Response {
        $task = $factory->make($connection);

        $dispatcher->subscribe($cloudTasks);
        $dispatcher->dispatch(new Events\TaskStarted(
            $task
        ));

        $this->worker
            ->setName($request->fingerprint())
            ->setCache($cache)
            ->runNextJob(
                $connection,
                $task->queueName(),
                $this->gatherWorkerOptions(),
            );

        $response = $cloudTasks->getResponse() ?? new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);

        $dispatcher->dispatch(new Events\TaskFinished(
            $task,
            $cloudTasks->getResult(),
            $response
        ));

        return $response;
    }

    protected function gatherWorkerOptions(): WorkerOptions
    {
        return new WorkerOptions('google-cloud-tasks', 0, 128, 60, 3, 3);
    }
}
