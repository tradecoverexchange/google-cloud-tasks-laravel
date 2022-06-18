<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Commands;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\Queue;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudQueueModifier;
use TradeCoverExchange\GoogleCloudTaskLaravel\QueueInteractionCommand;

class CreateQueueCommand extends QueueInteractionCommand
{
    protected $signature = 'google:cloud:queue:create {name}';
    protected $description = 'Creates a google tasks queue';

    /**
     * @throws \Google\ApiCore\ApiException
     */
    public function action(
        string $namespaceName,
        string|null $queueName,
        CloudTasksClient $client,
        array $config,
        CloudQueueModifier $queueModifier
    ): int {
        $location = CloudTasksClient::locationName($config['project_id'], $config['location']);
        $cloudQueue = new Queue();
        $cloudQueue->setName(
            CloudTasksClient::queueName(
            $config['project_id'],
            $config['location'],
            $queueName ?? $config['queue']
        )
        );
        $cloudQueue = $queueModifier->apply($cloudQueue, $config['settings'] ?? []);
        $client->createQueue($location, $cloudQueue);

        $this->line("Queue {$namespaceName} has been created.");

        return 0;
    }
}
