<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Commands;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\Queue\State;
use Google\Protobuf\FieldMask;
use TradeCoverExchange\GoogleCloudTaskLaravel\QueueInteractionCommand;

class QueueStatsCommand extends QueueInteractionCommand
{
    protected $signature = 'google:cloud:queue:stats {name}';
    protected $description = 'A command for display stats for a google tasks queue';

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Google\ApiCore\ApiException
     */
    public function action(CloudTasksClient $client, string $namespaceName, string|null $queueName, array $config): int
    {
        $cloudQueueName = CloudTasksClient::queueName(
            $config['project_id'],
            $config['location'],
            $queueName ?? $config['queue']
        );
        $cloudQueue = $client->getQueue($cloudQueueName, [
            'readMask' => new FieldMask(['paths' => ['state', 'stats']]),
        ]);
        $stats = $cloudQueue->getStats();

        $this->table([
            'queue',
            'state',
            'concurrent dispatches',
            'execution rate',
            'task count',
            'oldest arrival time',
        ], [[
            $namespaceName,
            State::name($cloudQueue->getState()),
            $stats->getConcurrentDispatchesCount(),
            $stats->getEffectiveExecutionRate(),
            $stats->getTasksCount(),
            $stats->getOldestEstimatedArrivalTime()?->toDateTime()->format(\DateTime::ATOM),
        ]]);

        return 0;
    }
}
