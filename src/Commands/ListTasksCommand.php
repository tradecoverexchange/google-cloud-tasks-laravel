<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Commands;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\Task;
use Illuminate\Support\LazyCollection;
use TradeCoverExchange\GoogleCloudTaskLaravel\QueueInteractionCommand;

class ListTasksCommand extends QueueInteractionCommand
{
    protected $signature = 'google:cloud:queue:tasks {name}';
    protected $description = 'Lists all tasks for a google tasks queue';

    /**
     * @throws \Google\ApiCore\ApiException
     * @throws \Google\ApiCore\ValidationException
     */
    public function action(
        string|null $queueName,
        CloudTasksClient $client,
        array $config,
    ): int {
        $cloudQueueName = CloudTasksClient::queueName(
            $config['project_id'], $config['location'], $queueName ?? $config['queue']
        );

        $pagedResponse = $client->listTasks($cloudQueueName);

        $collection = (new LazyCollection($pagedResponse->iterateAllElements()))
            ->map(fn (Task $task) => [
                $task->getName(),
                $task->getDispatchCount(),
                $task->getLastAttempt()?->getDispatchTime()->toDateTime()->format(DATE_ATOM),
                $task->getFirstAttempt()?->getDispatchTime()->toDateTime()->format(DATE_ATOM),
                $task->getCreateTime()?->toDateTime()->format(DATE_ATOM),
                $task->getCreateTime()?->toDateTime()->format(DATE_ATOM),
            ]);

        $this->table([
            'name',
            'dispatch count',
            'last attempt at',
            'first attempt at',
            'created at',
            'scheduled at',
        ], $collection->all());

        return 0;
    }
}
